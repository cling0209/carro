<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImportService;
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

    public function importForm(): View
    {
        return view('admin.products.import');
    }

    public function downloadImportTemplate(ProductImportService $importService): StreamedResponse
    {
        $content = $importService->generateTemplateCsv();

        return response()->streamDownload(
            fn () => print($content),
            'plantilla_productos.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function storeImport(Request $request, ProductImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importService->importFromUploadedFile($request->file('file'));

        $parts = [];

        if ($result['created'] > 0) {
            $parts[] = $result['created'].' creado(s)';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' actualizado(s)';
        }

        if ($result['reactivated'] > 0) {
            $parts[] = $result['reactivated'].' reactivado(s)';
        }

        if ($parts === []) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'No se importó ningún producto.')
                ->with('import_errors', array_slice($result['errors'], 0, 20));
        }

        $redirect = redirect()
            ->route('admin.products.index')
            ->with('success', 'Importación completada: '.implode(', ', $parts).'.');

        if ($result['errors'] !== []) {
            $redirect->with('import_errors', array_slice($result['errors'], 0, 20));

            if (count($result['errors']) > 20) {
                $redirect->with('error', 'Algunas filas fallaron. Se muestran los primeros 20 errores.');
            }
        }

        return $redirect;
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
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
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
