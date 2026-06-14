<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImportRun;
use App\Services\ProductCatalogImportService;
use App\Services\ProductChunkUploadService;
use App\Services\ProductImportJobService;
use App\Services\ProductImportLockService;
use App\Services\ProductImportProgressService;
use App\Services\ProductImportRunService;
use App\Services\ProductImportService;
use App\Services\ProductImportStagingService;
use App\Support\ProductImportColumnMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('q'), fn ($q) => $q->search($request->query('q')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $trashed = Product::onlyTrashed()->where('sku', $data['sku'])->first();

        if ($trashed) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), $trashed->id);
            $trashed->restore();
            $trashed->update($data);

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Producto reactivado correctamente.');
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']));
        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        $product->load('category');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product->id);

        if (isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $product->id);
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->archive();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto dado de baja del catálogo.');
    }

    public function importForm(ProductImportLockService $importLock): View
    {
        return view('admin.products.import', [
            'activeImport' => $importLock->currentOrReleaseIfAbandoned(),
            'mappableFields' => ProductImportColumnMapping::fieldDefinitions(),
        ]);
    }

    public function importStatus(ProductImportLockService $importLock): JsonResponse
    {
        $current = $importLock->currentOrReleaseIfAbandoned();

        return response()->json([
            'active' => $current !== null,
            'lock' => $current,
        ]);
    }

    public function importProgress(Request $request, ProductImportProgressService $progressService): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
        ]);

        $progress = $progressService->read($data['upload_id']);

        if ($progress === null) {
            return response()->json(['message' => 'No hay progreso para esta importación.'], 404);
        }

        if ((int) ($progress['user_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($progressService->enrichForPoll($data['upload_id'], $progress));
    }

    public function startBackgroundImport(Request $request, ProductImportJobService $importJob): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'mode' => ['nullable', 'in:template,custom'],
            'mapping' => ['nullable', 'array'],
        ]);

        $mode = (string) ($data['mode'] ?? 'template');
        $mapping = isset($data['mapping']) && is_array($data['mapping'])
            ? $this->normalizeColumnMapping($data['mapping'])
            : null;

        try {
            $importJob->queueBackgroundImport(
                $data['upload_id'],
                (int) $request->user()->id,
                $mode,
                $mapping,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                $this->importConflictStatus($e),
            );
        } catch (\Throwable $e) {
            report($e);

            app(ProductImportProgressService::class)->fail(
                $data['upload_id'],
                config('app.debug') ? $e->getMessage() : 'No se pudo encolar la importación en segundo plano.',
            );
            app(ProductImportLockService::class)->release($data['upload_id']);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'No se pudo iniciar la importación en segundo plano. Verifique QUEUE_CONNECTION=redis y que el worker esté activo.',
            ], 500);
        }

        return response()->json([
            'queued' => true,
            'upload_id' => $data['upload_id'],
        ]);
    }

    public function releaseImportLock(Request $request, ProductImportLockService $importLock): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['nullable', 'uuid'],
        ]);

        $importLock->releaseFully($data['upload_id'] ?? null);

        return response()->json(['released' => true]);
    }

    public function importResult(int $run, ProductImportRunService $runService): View
    {
        return view('admin.products.import-resultado', [
            'run' => $runService->findRun($run),
        ]);
    }

    public function importErrors(Request $request, int $run, ProductImportRunService $runService): View
    {
        $runModel = $runService->findRun($run);

        return view('admin.products.import-errores', [
            'run' => $runModel,
            'errores' => $runService->paginateErrors($runModel, 50),
        ]);
    }

    public function exportImportErrors(int $run, ProductImportRunService $runService): StreamedResponse
    {
        $runModel = $runService->findRun($run);
        abort_unless($runModel->tieneErrores(), 404);

        return $runService->exportErrorsCsvResponse($runModel);
    }

    public function downloadImportTemplate(ProductCatalogImportService $importService): StreamedResponse
    {
        return $importService->templateCsvDownloadResponse();
    }

    public function downloadImportTemplateExcel(ProductCatalogImportService $importService): StreamedResponse
    {
        return $importService->templateExcelDownloadResponse();
    }

    public function exportProducts(ProductImportService $importService): StreamedResponse
    {
        return $importService->exportProductsCsvResponse();
    }

    public function storeImportChunk(Request $request, ProductChunkUploadService $chunkUpload): JsonResponse
    {
        if (! $request->hasFile('chunk') || ! $request->file('chunk')->isValid()) {
            return response()->json([
                'message' => 'El fragmento no llegó al servidor. Reintenta la carga.',
            ], 422);
        }

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:500'],
            'original_name' => ['required', 'string', 'max:255'],
            'mode' => ['nullable', 'in:template,custom'],
            'chunk' => ['required', 'file', 'max:7168'],
        ]);

        $mode = $data['mode'] ?? 'template';
        $username = (string) ($request->user()->name ?: $request->user()->email);

        try {
            $result = $chunkUpload->storeChunk(
                $data['upload_id'],
                (int) $data['chunk_index'],
                (int) $data['total_chunks'],
                $data['original_name'],
                $request->file('chunk'),
                (int) $request->user()->id,
                $username,
                $mode,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                $this->importConflictStatus($e),
            );
        } catch (\Throwable $e) {
            report($e);

            app(ProductImportLockService::class)->release($data['upload_id']);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error interno al procesar la carga. Reintenta en unos minutos.',
            ], 500);
        }

        if (! $result['ready']) {
            return response()->json([
                'done' => false,
                'received' => (int) $data['chunk_index'] + 1,
                'total' => (int) $data['total_chunks'],
            ]);
        }

        return response()->json([
            'done' => true,
            'mode' => $result['mode'] ?? 'template',
            'upload_id' => $result['upload_id'],
            'pending_parse' => $result['pending_parse'] ?? false,
            'stream_mode' => $result['stream_mode'] ?? false,
            'ready_to_process' => $result['ready_to_process'] ?? false,
            'batch_count' => $result['batch_count'] ?? null,
            'columns' => $result['columns'] ?? null,
            'total_rows' => $result['total_rows'] ?? null,
            'suggested_mapping' => $result['suggested_mapping'] ?? null,
        ]);
    }

    public function initializeCustomImport(Request $request, ProductImportStagingService $staging): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
        ]);

        try {
            $initialized = $staging->initializeFromPending(
                $data['upload_id'],
                (int) $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error al analizar el archivo Excel.',
            ], 500);
        }

        return response()->json($initialized);
    }

    public function previewImportMapping(Request $request, ProductImportStagingService $staging): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'mapping' => ['required', 'array'],
        ]);

        try {
            $mapping = $this->normalizeColumnMapping($data['mapping']);
            $preview = $staging->preview(
                $data['upload_id'],
                (int) $request->user()->id,
                $mapping,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($preview);
    }

    public function prepareCustomImport(Request $request, ProductImportJobService $importJob): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'mapping' => ['required', 'array'],
        ]);

        try {
            $mapping = $this->normalizeColumnMapping($data['mapping']);
            $prepared = $importJob->continuePrepareCustom(
                $data['upload_id'],
                (int) $request->user()->id,
                $mapping,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                $this->importConflictStatus($e),
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error al preparar la importación.',
            ], 500);
        }

        return response()->json($prepared);
    }

    public function prepareTemplateImport(Request $request, ProductImportJobService $importJob): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
        ]);

        try {
            $prepared = $importJob->continuePrepareTemplate(
                $data['upload_id'],
                (int) $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            app(ProductImportLockService::class)->release($data['upload_id']);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error al analizar el archivo Excel.',
            ], 500);
        }

        return response()->json($prepared);
    }

    public function processImportBatch(Request $request, ProductImportJobService $importJob): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
        ]);

        try {
            $progress = $importJob->processNextBatchWithRun(
                $data['upload_id'],
                (int) $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                $this->importConflictStatus($e),
            );
        } catch (\Throwable $e) {
            report($e);

            app(ProductImportLockService::class)->release($data['upload_id']);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error interno al importar productos. Reintenta en unos minutos.',
            ], 500);
        }

        $payload = [
            'finished' => $progress['finished'],
            'processed_batches' => $progress['processed_batches'],
            'total_batches' => $progress['total_batches'],
            'import_mode' => $progress['import_mode'] ?? ProductImportJobService::IMPORT_MODE_BATCH,
        ];

        if (isset($progress['processed_rows'])) {
            $payload['processed_rows'] = $progress['processed_rows'];
        }

        if (isset($progress['total_rows'])) {
            $payload['total_rows'] = $progress['total_rows'];
        }

        if (isset($progress['result']) && is_array($progress['result'])) {
            $payload['result'] = [
                'created' => (int) ($progress['result']['created'] ?? 0),
                'updated' => (int) ($progress['result']['updated'] ?? 0),
                'skipped' => (int) ($progress['result']['skipped'] ?? 0),
            ];
        }

        if ($progress['finished'] && isset($progress['run_id'])) {
            $run = ProductImportRun::query()->findOrFail($progress['run_id']);
            $payload['redirect'] = app(ProductImportRunService::class)->redirectUrlForRun($run);
        }

        return response()->json($payload);
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, string>
     */
    protected function normalizeColumnMapping(array $mapping): array
    {
        $normalized = [];

        foreach (ProductImportColumnMapping::FIELDS as $field => $meta) {
            $value = trim((string) ($mapping[$field] ?? ''));
            $normalized[$field] = $value !== '' ? $value : '';
        }

        ProductImportColumnMapping::validate($normalized);

        return $normalized;
    }

    protected function importConflictStatus(\InvalidArgumentException $exception): int
    {
        return str_contains($exception->getMessage(), 'importación en curso') ? 409 : 422;
    }

    protected function validated(Request $request, ?int $productId = null): array
    {
        $skuRule = Rule::unique('products', 'sku')
            ->where(fn ($q) => $q->whereNull('deleted_at'));
        $slugRule = Rule::unique('products', 'slug')
            ->where(fn ($q) => $q->whereNull('deleted_at'));

        if ($productId) {
            $skuRule->ignore($productId);
            $slugRule->ignore($productId);
        }

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:60', $skuRule],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', $slugRule],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'familia' => ['nullable', 'string', 'max:120'],
            'image_filename' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    protected function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $base = Str::slug($slug) ?: 'producto';
        $candidate = $base;
        $i = 1;

        while (Product::withTrashed()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
