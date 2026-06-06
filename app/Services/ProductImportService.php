<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductImportService
{
    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'sku',
            'nombre',
            'precio',
            'stock',
            'familia',
            'slug',
            'descripcion',
            'precio_referencia',
            'peso_kg',
            'activo',
            'destacado',
            'nombre_archivo',
        ];
    }

    public function generateTemplateCsv(): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->templateHeaders(), ';');
        fputcsv($handle, [
            'AUD-001',
            'Audífonos Bluetooth Pro',
            '29990',
            '45',
            'LIB',
            'audifonos-bluetooth-pro',
            'Descripción opcional del producto',
            '39990',
            '0.35',
            '1',
            '1',
            '90503_medium.jpg',
        ], ';');

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    /**
     * @return array{created: int, updated: int, reactivated: int, skipped: int, errors: list<string>}
     */
    public function importFromUploadedFile(UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);

        if ($rows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'reactivated' => 0,
                'skipped' => 0,
                'errors' => ['El archivo no contiene filas de datos.'],
            ];
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'reactivated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $lineNumber => $row) {
            $outcome = $this->importRow($row, $lineNumber);

            if ($outcome['error'] !== null) {
                $result['errors'][] = $outcome['error'];
                $result['skipped']++;

                continue;
            }

            $result[$outcome['action']]++;
        }

        return $result;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $content = $this->readFileAsUtf8($file);

        if ($content === '') {
            return [];
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return [];
        }

        fwrite($handle, $content);
        rewind($handle);

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $headers = $this->normalizeHeaders(str_getcsv($firstLine, $delimiter));
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $this->ensureUtf8(trim((string) ($data[$index] ?? '')));
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function readFileAsUtf8(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return '';
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            return '';
        }

        return $this->ensureUtf8($raw);
    }

    protected function ensureUtf8(string $value): string
    {
        $value = $this->stripBom($value);

        if ($value === '') {
            return '';
        }

        if ($this->isValidUtf8($value)) {
            return $value;
        }

        foreach (['Windows-1252', 'ISO-8859-1', 'CP1252'] as $encoding) {
            $converted = mb_convert_encoding($value, 'UTF-8', $encoding);

            if (mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        $converted = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return $converted !== false ? $converted : $value;
    }

    protected function isValidUtf8(string $value): bool
    {
        if (! mb_check_encoding($value, 'UTF-8')) {
            return false;
        }

        return preg_match('//u', $value) === 1;
    }

    /**
     * @param  list<string|null>  $data
     */
    protected function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function stripBom(string $line): string
    {
        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            return substr($line, 3);
        }

        return $line;
    }

    /**
     * @param  list<string|null>  $headers
     * @return list<string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        $aliases = [
            'name' => 'nombre',
            'price' => 'precio',
            'description' => 'descripcion',
            'compare_at_price' => 'precio_referencia',
            'weight_kg' => 'peso_kg',
            'is_active' => 'activo',
            'is_featured' => 'destacado',
            'image_filename' => 'nombre_archivo',
            'archivo_imagen' => 'nombre_archivo',
        ];

        return array_map(function (?string $header) use ($aliases) {
            $normalized = Str::lower(trim((string) $header));
            $normalized = str_replace([' ', '-'], '_', $normalized);

            return $aliases[$normalized] ?? $normalized;
        }, $headers);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{action: string, error: string|null}
     */
    protected function importRow(array $row, int $lineNumber): array
    {
        $displayLine = $lineNumber + 2;

        $validator = Validator::make($row, [
            'sku' => ['required', 'string', 'max:60'],
            'nombre' => ['required', 'string', 'max:200'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'familia' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'precio_referencia' => ['nullable', 'numeric', 'min:0'],
            'peso_kg' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['nullable'],
            'destacado' => ['nullable'],
            'nombre_archivo' => ['nullable', 'string', 'max:255'],
        ], [], [
            'sku' => 'sku',
            'nombre' => 'nombre',
            'precio' => 'precio',
            'stock' => 'stock',
            'familia' => 'familia',
        ]);

        if ($validator->fails()) {
            return [
                'action' => 'skipped',
                'error' => 'Fila '.$displayLine.': '.$validator->errors()->first(),
            ];
        }

        $validated = $validator->validated();
        $familia = trim($validated['familia']);
        $category = $this->resolveCategoryFromFamilia($familia);

        if (! $category) {
            return [
                'action' => 'skipped',
                'error' => 'Fila '.$displayLine.': no existe categoría para la familia "'.$familia.'".',
            ];
        }

        $payload = [
            'category_id' => $category->id,
            'sku' => $validated['sku'],
            'name' => $validated['nombre'],
            'description' => $validated['descripcion'] ?? null,
            'price' => $validated['precio'],
            'compare_at_price' => $this->nullableNumeric($validated['precio_referencia'] ?? null),
            'stock' => (int) $validated['stock'],
            'weight_kg' => $this->nullableNumeric($validated['peso_kg'] ?? null),
            'is_active' => $this->parseBoolean($validated['activo'] ?? null, true),
            'is_featured' => $this->parseBoolean($validated['destacado'] ?? null, false),
            'familia' => $familia,
            'image_filename' => $validated['nombre_archivo'] ?? null,
        ];

        $existing = Product::withTrashed()->where('sku', $payload['sku'])->first();

        if ($existing && ! $existing->trashed()) {
            $slug = $validated['slug'] ?? $existing->slug;

            $slugValidator = Validator::make(
                ['slug' => $slug],
                ['slug' => ['required', 'string', 'max:200', Rule::unique('products', 'slug')->where(fn ($q) => $q->whereNull('deleted_at'))->ignore($existing->id)]]
            );

            if ($slugValidator->fails()) {
                return [
                    'action' => 'skipped',
                    'error' => 'Fila '.$displayLine.': '.$slugValidator->errors()->first(),
                ];
            }

            $payload['slug'] = $this->uniqueSlug($slug, $existing->id, $payload['sku']);

            return $this->persistProduct($existing, $payload, $displayLine, 'updated', reactivate: false);
        }

        if ($existing && $existing->trashed()) {
            $payload['slug'] = $this->uniqueSlug(
                $validated['slug'] ?? $this->defaultSlug($payload['name'], $payload['sku']),
                $existing->id,
                $payload['sku']
            );

            return $this->persistProduct($existing, $payload, $displayLine, 'reactivated', reactivate: true);
        }

        $payload['slug'] = $this->uniqueSlug(
            $validated['slug'] ?? $this->defaultSlug($payload['name'], $payload['sku']),
            sku: $payload['sku']
        );

        return $this->persistProduct(new Product, $payload, $displayLine, 'created');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{action: string, error: string|null}
     */
    protected function persistProduct(
        Product $product,
        array $payload,
        int $displayLine,
        string $action,
        bool $reactivate = false,
    ): array {
        try {
            if ($reactivate) {
                $product->restore();
            }

            if ($product->exists) {
                $product->update($payload);
            } else {
                $product->fill($payload);
                $product->save();
            }
        } catch (QueryException $e) {
            return [
                'action' => 'skipped',
                'error' => 'Fila '.$displayLine.': '.$this->friendlyDbError($e),
            ];
        }

        return ['action' => $action, 'error' => null];
    }

    protected function friendlyDbError(QueryException $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'invalid byte sequence for encoding "UTF8"')) {
            return 'texto con caracteres inválidos (guarda el CSV en UTF-8 o Latin-1/Windows).';
        }

        return 'error al guardar en la base de datos.';
    }

    protected function defaultSlug(string $name, string $sku): string
    {
        return Str::slug($name) ?: Str::slug($sku) ?: 'producto';
    }

    protected function resolveCategoryFromFamilia(string $familia): ?Category
    {
        $candidates = array_values(array_unique([
            $familia,
            Str::lower($familia),
            Str::slug($familia),
        ]));

        return Category::query()
            ->whereNull('deleted_at')
            ->whereIn('slug', $candidates)
            ->first();
    }

    protected function nullableNumeric(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (float) $value;
    }

    protected function parseBoolean(?string $value, bool $default): bool
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        $normalized = Str::lower(trim($value));

        return in_array($normalized, ['1', 'true', 'si', 'sí', 'yes', 'activo', 'on'], true);
    }

    protected function uniqueSlug(string $slug, ?int $exceptId = null, ?string $sku = null): string
    {
        $base = Str::slug($slug) ?: ($sku ? Str::slug($sku) : '') ?: 'producto';
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
