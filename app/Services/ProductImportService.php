<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
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
            'categoria_slug',
            'slug',
            'descripcion',
            'precio_referencia',
            'peso_kg',
            'activo',
            'destacado',
            'familia',
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
            'electronica',
            'audifonos-bluetooth-pro',
            'Descripción opcional del producto',
            '39990',
            '0.35',
            '1',
            '1',
            'LIB',
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
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $firstLine = $this->stripBom($firstLine);
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

                $row[$header] = trim((string) ($data[$index] ?? ''));
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
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
            'category_slug' => 'categoria_slug',
            'categoria' => 'categoria_slug',
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
            'categoria_slug' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'precio_referencia' => ['nullable', 'numeric', 'min:0'],
            'peso_kg' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['nullable'],
            'destacado' => ['nullable'],
            'familia' => ['nullable', 'string', 'max:120'],
            'nombre_archivo' => ['nullable', 'string', 'max:255'],
        ], [], [
            'sku' => 'sku',
            'nombre' => 'nombre',
            'precio' => 'precio',
            'stock' => 'stock',
            'categoria_slug' => 'categoria_slug',
        ]);

        if ($validator->fails()) {
            return [
                'action' => 'skipped',
                'error' => 'Fila '.$displayLine.': '.$validator->errors()->first(),
            ];
        }

        $validated = $validator->validated();
        $categoryId = null;

        if (! empty($validated['categoria_slug'])) {
            $category = Category::query()
                ->where('slug', $validated['categoria_slug'])
                ->whereNull('deleted_at')
                ->first();

            if (! $category) {
                return [
                    'action' => 'skipped',
                    'error' => 'Fila '.$displayLine.': categoría "'.$validated['categoria_slug'].'" no existe.',
                ];
            }

            $categoryId = $category->id;
        }

        $payload = [
            'category_id' => $categoryId,
            'sku' => $validated['sku'],
            'name' => $validated['nombre'],
            'description' => $validated['descripcion'] ?? null,
            'price' => $validated['precio'],
            'compare_at_price' => $this->nullableNumeric($validated['precio_referencia'] ?? null),
            'stock' => (int) $validated['stock'],
            'weight_kg' => $this->nullableNumeric($validated['peso_kg'] ?? null),
            'is_active' => $this->parseBoolean($validated['activo'] ?? null, true),
            'is_featured' => $this->parseBoolean($validated['destacado'] ?? null, false),
            'familia' => $validated['familia'] ?? null,
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

            $payload['slug'] = $this->uniqueSlug($slug, $existing->id);
            $existing->update($payload);

            return ['action' => 'updated', 'error' => null];
        }

        if ($existing && $existing->trashed()) {
            $payload['slug'] = $this->uniqueSlug(
                $validated['slug'] ?? Str::slug($payload['name']),
                $existing->id
            );
            $existing->restore();
            $existing->update($payload);

            return ['action' => 'reactivated', 'error' => null];
        }

        $payload['slug'] = $this->uniqueSlug($validated['slug'] ?? Str::slug($payload['name']));
        Product::create($payload);

        return ['action' => 'created', 'error' => null];
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
