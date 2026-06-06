<?php

namespace App\Services\Admin;

use App\Models\ShippingComunaWeightRate;
use App\Services\ProductImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingWeightRateImportService
{
    public function __construct(protected ProductImportService $csv) {}

    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'region',
            'comuna',
            'etiqueta',
            'peso_min_kg',
            'peso_max_kg',
            'adicional_clp',
            'orden',
            'activo',
        ];
    }

    public function generateTemplateCsv(): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->templateHeaders(), ';');
        fputcsv($handle, [
            'Región de Valparaíso',
            'Viña del Mar',
            'Hasta 1 kg',
            '0',
            '1',
            '4990',
            '1',
            '1',
        ], ';');
        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    public function exportCsvResponse(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_merge(['id'], $this->templateHeaders()), ';');

            ShippingComunaWeightRate::query()
                ->orderBy('region')
                ->orderBy('comuna')
                ->orderBy('sort_order')
                ->orderBy('min_weight_kg')
                ->chunk(500, function ($rates) use ($handle) {
                    foreach ($rates as $rate) {
                        fputcsv($handle, [
                            $rate->id,
                            $rate->region,
                            $rate->comuna,
                            $rate->label,
                            $this->formatDecimal($rate->min_weight_kg),
                            $rate->max_weight_kg !== null ? $this->formatDecimal($rate->max_weight_kg) : '',
                            number_format((float) $rate->price, 0, '', ''),
                            $rate->sort_order,
                            $rate->is_active ? '1' : '0',
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'tramos_peso_envio_'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function importFromUploadedFile(UploadedFile $file): array
    {
        $rows = $this->csv->parseUploadedCsv($file);

        if ($rows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['El archivo está vacío o no tiene filas válidas.'],
            ];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$created, &$updated, &$skipped, &$errors) {
            foreach ($rows as $lineNumber => $row) {
                $displayLine = $lineNumber + 2;
                $normalized = $this->normalizeRow($row);

                $validator = Validator::make($normalized, [
                    'id' => ['nullable', 'integer', 'exists:shipping_comuna_weight_rates,id'],
                    'region' => ['required', 'string', 'max:80'],
                    'comuna' => ['required', 'string', 'max:80'],
                    'etiqueta' => ['required', 'string', 'max:120'],
                    'peso_min_kg' => ['required', 'numeric', 'min:0'],
                    'peso_max_kg' => ['nullable', 'numeric', 'gt:peso_min_kg'],
                    'adicional_clp' => ['required', 'numeric', 'min:0'],
                    'orden' => ['nullable', 'integer', 'min:0'],
                    'activo' => ['nullable'],
                ]);

                if ($validator->fails()) {
                    $errors[] = 'Fila '.$displayLine.': '.$validator->errors()->first();
                    $skipped++;

                    continue;
                }

                $data = $validator->validated();

                if (! ShippingComunaWeightRate::isValidComuna($data['region'], $data['comuna'])) {
                    $errors[] = 'Fila '.$displayLine.': la comuna no pertenece a la región indicada.';
                    $skipped++;

                    continue;
                }

                $payload = [
                    'region' => $data['region'],
                    'comuna' => $data['comuna'],
                    'label' => $data['etiqueta'],
                    'min_weight_kg' => $data['peso_min_kg'],
                    'max_weight_kg' => $data['peso_max_kg'] ?? null,
                    'price' => $data['adicional_clp'],
                    'sort_order' => $data['orden'] ?? 0,
                    'is_active' => $this->parseBoolean($data['activo'] ?? null, true),
                ];

                $rate = null;

                if (! empty($data['id'])) {
                    $rate = ShippingComunaWeightRate::query()->find($data['id']);
                }

                if (! $rate) {
                    $rate = ShippingComunaWeightRate::query()
                        ->where('region', $payload['region'])
                        ->where('comuna', $payload['comuna'])
                        ->where('label', $payload['label'])
                        ->first();
                }

                if ($rate) {
                    $rate->update($payload);
                    $updated++;
                } else {
                    ShippingComunaWeightRate::create($payload);
                    $created++;
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $aliases = [
            'label' => 'etiqueta',
            'min_weight_kg' => 'peso_min_kg',
            'max_weight_kg' => 'peso_max_kg',
            'price' => 'adicional_clp',
            'sort_order' => 'orden',
            'is_active' => 'activo',
        ];

        $normalized = [];

        foreach ($row as $key => $value) {
            $header = Str::lower(trim(str_replace([' ', '-'], '_', $key)));
            $header = $aliases[$header] ?? $header;
            $normalized[$header] = trim((string) $value);
        }

        if (($normalized['peso_max_kg'] ?? '') === '') {
            $normalized['peso_max_kg'] = null;
        }

        if (($normalized['id'] ?? '') === '') {
            unset($normalized['id']);
        }

        return $normalized;
    }

    protected function parseBoolean(?string $value, bool $default): bool
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return in_array(Str::lower(trim($value)), ['1', 'true', 'si', 'sí', 'yes', 'on'], true);
    }

    protected function formatDecimal(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }
}
