<?php

namespace App\Services;

use App\Models\Product;
use App\Support\ProductImportColumnMapping;
use App\Support\ProductImportError;
use App\Support\ProductImportHeartbeat;
use App\Support\ProductImportFileTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCatalogImportService
{
    public const UPSERT_CHUNK_SIZE = 5000;
    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'codigo' => 'prod_item',
        'prod_item' => 'prod_item',
        'sku' => 'prod_item',
        'nombre' => 'prod_nombre',
        'prod_nombre' => 'prod_nombre',
        'descripcion' => 'prod_nombre',
        'familia' => 'prod_familia',
        'prod_familia' => 'prod_familia',
        'precio' => 'prod_valor',
        'prod_valor' => 'prod_valor',
        'costo' => 'prod_valor_costo',
        'prod_valor_costo' => 'prod_valor_costo',
        'nombre_archivo' => 'prod_imagen',
        'prod_imagen' => 'prod_imagen',
        'imagen' => 'prod_imagen',
        'gramaje' => 'prod_gramaje',
        'prod_gramaje' => 'prod_gramaje',
        'stock' => 'prod_stock_real',
        'prod_stock_real' => 'prod_stock_real',
        'softland' => 'prod_item_softland',
        'prod_item_softland' => 'prod_item_softland',
        'slug' => 'slug',
        'descripcion' => 'descripcion',
        'description' => 'descripcion',
        'precio_referencia' => 'prod_valor_costo',
        'compare_at_price' => 'prod_valor_costo',
        'peso_kg' => 'peso_kg',
        'weight_kg' => 'peso_kg',
        'activo' => 'activo',
        'is_active' => 'activo',
        'destacado' => 'destacado',
        'is_featured' => 'destacado',
    ];

    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return app(ProductImportService::class)->templateHeaders();
    }

    public function templateCsvDownloadResponse(): StreamedResponse
    {
        $content = app(ProductImportService::class)->generateTemplateCsv();

        return response()->streamDownload(
            fn () => print($content),
            'plantilla_productos.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function templateExcelDownloadResponse(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $headers = $this->templateHeaders();
            $sheet->fromArray([$headers, $this->templateExampleRow()]);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'plantilla_productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<string|int|float>
     */
    private function templateExampleRow(): array
    {
        return [
            'DEMO001',
            'Papel bond A4 demo',
            '19990',
            '100',
            'PAPEL',
            'papel-bond-a4-demo',
            'Resma A4 500 hojas',
            '',
            '',
            '1',
            '1',
            'DEMO001_medium.jpg',
        ];
    }

    public function exportCsvResponse(): StreamedResponse
    {
        return app(ProductImportService::class)->exportProductsCsvResponse();
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}
     */
    public function emptyResult(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    /**
     * @param  list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>  $errors
     */
    public function errorsCsvDownloadResponse(array $errors): StreamedResponse
    {
        return response()->streamDownload(function () use ($errors) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['fila', 'codigo', 'nombre', 'familia', 'error', 'detalle'], ';');

            foreach ($errors as $error) {
                fputcsv($handle, [
                    $error['fila'] ?? '',
                    $error['codigo'] ?? '',
                    $error['nombre'] ?? '',
                    $error['familia'] ?? '',
                    $error['mensaje'] ?? '',
                    $error['detalle'] ?? '',
                ], ';');
            }

            fclose($handle);
        }, 'errores_importacion_productos_'.now()->format('Y-m-d_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}
     */
    public function importFromUploadedFile(UploadedFile $file, ?string $usuarioUpd = null, ?array $columnMapping = null): array
    {
        $rows = $this->parseRowsFromUploadedFile($file);

        if ($rows === []) {
            return array_merge($this->emptyResult(), [
                'errors' => [ProductImportError::general('El archivo no contiene filas de datos.')],
            ]);
        }

        return $this->importRows($rows, $usuarioUpd, $columnMapping);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}
     */
    public function importFromPath(string $path, ?string $usuarioUpd = null, ?array $columnMapping = null): array
    {
        $rows = $this->parseRowsFromPath($path);

        if ($rows === []) {
            return array_merge($this->emptyResult(), [
                'errors' => [ProductImportError::general('El archivo no contiene filas de datos.')],
            ]);
        }

        return $this->importRows($rows, $usuarioUpd, $columnMapping);
    }

    /**
     * Lee un tramo del CSV e importa fila a fila sin cargar el archivo completo en memoria.
     *
     * @param  list<string>  $dataHeaders
     * @return array{
     *     chunk_result: array{created: int, updated: int, skipped: int, errors: list<array<string, mixed>>},
     *     rows_read: int,
     *     next_physical_row: int,
     *     exhausted: bool
     * }
     */
    public function importFromCsvStreamPath(
        string $path,
        int $nextPhysicalRow,
        int $maxRows,
        ?string $usuarioUpd = null,
        ?array $columnMapping = null,
        array $dataHeaders = [],
        string $delimiter = ';',
    ): array {
        if ($maxRows < 1) {
            throw new \InvalidArgumentException('El tamaño del tramo de importación debe ser mayor que cero.');
        }

        if (! is_file($path)) {
            throw new \InvalidArgumentException('No se encontró el archivo CSV a importar.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV a importar.');
        }

        try {
            if ($nextPhysicalRow < 2) {
                $nextPhysicalRow = 2;
            }

            $this->seekCsvPhysicalLine($handle, $delimiter, $nextPhysicalRow);

            $rows = [];
            $rowsRead = 0;
            $physicalLine = $nextPhysicalRow - 1;
            $exhausted = false;

            while ($rowsRead < $maxRows) {
                $data = fgetcsv($handle, 0, $delimiter);

                if ($data === false) {
                    $exhausted = true;
                    break;
                }

                $physicalLine++;

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $row = ['_csv_line' => (string) $physicalLine];
                foreach ($dataHeaders as $columnIndex => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $row[$header] = trim((string) ($data[$columnIndex] ?? ''));
                }

                if (count($row) > 1) {
                    $rows[] = $row;
                    $rowsRead++;
                }
            }

            if ($rows === [] && $exhausted) {
                return [
                    'chunk_result' => $this->emptyResult(),
                    'rows_read' => 0,
                    'next_physical_row' => $physicalLine + 1,
                    'exhausted' => true,
                ];
            }

            return [
                'chunk_result' => $this->importRows($rows, $usuarioUpd, $columnMapping),
                'rows_read' => $rowsRead,
                'next_physical_row' => $physicalLine + 1,
                'exhausted' => $exhausted,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $columnMapping
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{crear: int, actualizar: int, error: int},
     *     total_rows: int,
     *     preview_limit: int
     * }
     */
    public function previewFromPath(string $path, array $columnMapping, int $limit = 10, ?string $originalName = null): array
    {
        $extension = ProductImportFileTypes::extensionFromName($originalName ?? basename($path));

        if (in_array($extension, ProductImportFileTypes::SPREADSHEET_EXTENSIONS, true)) {
            $metadata = app(ProductSpreadsheetReader::class)->readMetadata($path);
            $rows = app(ProductSpreadsheetReader::class)->readDataRows(
                $path,
                2,
                min(2 + max($limit, 1) + 20, max(2, (int) $metadata['highest_row'])),
                $metadata['headers'],
                (int) $metadata['column_count'],
            );
            $rows = array_slice($rows, 0, $limit);

            return $this->previewRows($rows, $columnMapping, $limit, max(0, (int) $metadata['highest_row'] - 1));
        }

        $rows = $this->parseCsvFromPathLimited($path, $limit + 20);

        return $this->previewRows($rows, $columnMapping, $limit);
    }

    /**
     * @param  array<string, string|null>  $columnMapping
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{crear: int, actualizar: int, error: int},
     *     total_rows: int,
     *     preview_limit: int
     * }
     */
    public function previewFromContent(string $content, array $columnMapping, int $limit = 10): array
    {
        $rows = $this->parseCsvText($content);

        return $this->previewRows($rows, $columnMapping, $limit);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string|null>|null  $columnMapping
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{crear: int, actualizar: int, error: int},
     *     total_rows: int,
     *     preview_limit: int
     * }
     */
    public function previewRows(array $rows, ?array $columnMapping, int $limit = 10, ?int $totalRows = null): array
    {
        $preview = [];
        $summary = ['crear' => 0, 'actualizar' => 0, 'error' => 0];
        $limit = max(1, min($limit, 50));
        app(ProductImportService::class)->ensureCategoryCacheLoaded();

        foreach (array_slice($rows, 0, $limit) as $lineNumber => $rawRow) {
            $csvLine = (int) ($rawRow['_csv_line'] ?? ($lineNumber + 2));
            $row = $this->mapRow($rawRow, $columnMapping);
            $validation = $this->validateRow($row, $csvLine);

            if ($validation !== null) {
                $preview[] = [
                    'fila' => $csvLine,
                    'accion' => 'error',
                    'codigo' => $row['prod_item'] ?? '',
                    'nombre' => $row['prod_nombre'] ?? '',
                    'familia' => $row['prod_familia'] ?? '',
                    'precio' => $row['prod_valor'] ?? '',
                    'mensaje' => $validation['mensaje'],
                    'detalle' => $validation['detalle'],
                ];
                $summary['error']++;

                continue;
            }

            $familia = trim((string) $row['prod_familia']);
            $categoryError = app(ProductImportService::class)->familiaImportError($familia);

            if ($categoryError !== null) {
                $preview[] = [
                    'fila' => $csvLine,
                    'accion' => 'error',
                    'codigo' => $row['prod_item'] ?? '',
                    'nombre' => $row['prod_nombre'] ?? '',
                    'familia' => $familia,
                    'precio' => $row['prod_valor'] ?? '',
                    'mensaje' => $categoryError,
                    'detalle' => null,
                ];
                $summary['error']++;

                continue;
            }

            $codigo = trim((string) $row['prod_item']);
            $existe = Product::withTrashed()->where('sku', $codigo)->exists();
            $accion = $existe ? 'actualizar' : 'crear';
            $summary[$accion]++;

            $preview[] = [
                'fila' => $csvLine,
                'accion' => $accion,
                'codigo' => $codigo,
                'nombre' => mb_strtoupper(trim((string) $row['prod_nombre'])),
                'familia' => trim((string) $row['prod_familia']),
                'precio' => (int) $row['prod_valor'],
                'costo' => $row['prod_valor_costo'] !== '' ? (int) $row['prod_valor_costo'] : null,
                'mensaje' => null,
                'detalle' => null,
            ];
        }

        return [
            'rows' => $preview,
            'summary' => $summary,
            'total_rows' => $totalRows ?? count($rows),
            'preview_limit' => $limit,
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string|null>|null  $columnMapping
     * @return array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}
     */
    public function importRows(array $rows, ?string $usuarioUpd = null, ?array $columnMapping = null): array
    {
        $result = $this->emptyResult();

        $pending = [];

        foreach ($rows as $lineNumber => $rawRow) {
            $csvLine = (int) ($rawRow['_csv_line'] ?? ($lineNumber + 2));
            $row = $this->mapRow($rawRow, $columnMapping);

            $validation = $this->validateRow($row, $csvLine);
            if ($validation !== null) {
                $this->pushError($result, $validation);
                $result['skipped']++;

                continue;
            }

            if (isset($rawRow['_csv_line'])) {
                $row['_csv_line'] = (string) $rawRow['_csv_line'];
            }

            $pending[] = $row;
        }

        $pending = $this->deduplicateRowsByItem($pending, $result);

        foreach (array_chunk($pending, self::UPSERT_CHUNK_SIZE) as $chunk) {
            $this->persistChunk($chunk, $usuarioUpd, $result);
        }

        return $result;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $result
     * @return list<array<string, string>>
     */
    private function deduplicateRowsByItem(array $rows, array &$result): array
    {
        /** @var array<string, array<string, string>> $unique */
        $unique = [];
        $duplicates = 0;

        foreach ($rows as $row) {
            $item = trim((string) $row['prod_item']);

            if (isset($unique[$item])) {
                $duplicates++;
            }

            $unique[$item] = $row;
        }

        if ($duplicates > 0) {
            $this->pushError($result, ProductImportError::general(
                "{$duplicates} fila(s) duplicada(s) en el CSV; se usó la última ocurrencia de cada código.",
            ));
        }

        return array_values($unique);
    }

    /**
     * @param  list<array<string, string>>  $chunk
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $result
     */
    private function persistChunk(array $chunk, ?string $usuarioUpd, array &$result): void
    {
        unset($usuarioUpd);

        $carroRows = array_map(fn (array $row) => $this->mappedRowToCarro($row), $chunk);

        try {
            $batchResult = app(ProductImportService::class)->importParsedRows($carroRows);
        } catch (\Throwable $exception) {
            $this->pushError($result, ProductImportError::general(
                'Error al guardar un lote de productos en la base de datos.',
            ));
            $this->pushError($result, ProductImportError::general(
                config('app.debug') ? $exception->getMessage() : 'Revise duplicados o datos inválidos en el lote.',
            ));

            throw $exception;
        }

        $result['created'] += $batchResult['created'];
        $result['updated'] += $batchResult['updated'] + $batchResult['reactivated'];
        $result['skipped'] += $batchResult['skipped'];

        foreach ($batchResult['errors'] as $error) {
            if (is_array($error)) {
                $this->pushError($result, [
                    'fila' => $error['fila'] ?? null,
                    'codigo' => $error['sku'] ?? '',
                    'nombre' => $error['nombre'] ?? '',
                    'familia' => $error['familia'] ?? '',
                    'mensaje' => $error['mensaje'] ?? '',
                    'detalle' => null,
                ]);

                continue;
            }

            $this->pushError($result, ProductImportError::general((string) $error));
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function mappedRowToCarro(array $row): array
    {
        $mapped = [
            'sku' => trim((string) ($row['prod_item'] ?? '')),
            'nombre' => trim((string) ($row['prod_nombre'] ?? '')),
            'familia' => trim((string) ($row['prod_familia'] ?? '')),
            'precio' => trim((string) ($row['prod_valor'] ?? '')),
            'stock' => trim((string) ($row['prod_stock_real'] ?? '0')) !== '' ? trim((string) $row['prod_stock_real']) : '0',
            'nombre_archivo' => trim((string) ($row['prod_imagen'] ?? '')),
            'precio_referencia' => trim((string) ($row['prod_valor_costo'] ?? '')),
            'slug' => trim((string) ($row['slug'] ?? '')),
            'descripcion' => trim((string) ($row['descripcion'] ?? '')),
            'peso_kg' => trim((string) ($row['peso_kg'] ?? '')),
            'activo' => trim((string) ($row['activo'] ?? '1')) !== '' ? trim((string) ($row['activo'] ?? '1')) : '1',
            'destacado' => trim((string) ($row['destacado'] ?? '0')) !== '' ? trim((string) ($row['destacado'] ?? '0')) : '0',
        ];

        if (isset($row['_csv_line']) && trim((string) $row['_csv_line']) !== '') {
            $mapped['_csv_line'] = trim((string) $row['_csv_line']);
        }

        return $mapped;
    }

    /**
     * @param  array{created: int, updated: int, skipped: int, errors: list<array<string, mixed>>}  $result
     * @param  array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}  $error
     */
    private function pushError(array &$result, array $error): void
    {
        $result['errors'][] = $error;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}|null
     */
    private function validateRow(array $row, int $fila): ?array
    {
        if (trim((string) ($row['prod_item'] ?? '')) === '') {
            return ProductImportError::row($fila, $row, 'Falta código.');
        }

        if (trim((string) ($row['prod_nombre'] ?? '')) === '') {
            return ProductImportError::row($fila, $row, 'Falta nombre.');
        }

        if (trim((string) ($row['prod_familia'] ?? '')) === '') {
            return ProductImportError::row($fila, $row, 'Falta familia.');
        }

        if (! is_numeric($row['prod_valor'] ?? null)) {
            return ProductImportError::row(
                $fila,
                $row,
                'Precio inválido.',
                'valor: '.($row['prod_valor'] ?? ''),
            );
        }

        if (isset($row['prod_valor_costo']) && $row['prod_valor_costo'] !== '' && ! is_numeric($row['prod_valor_costo'])) {
            return ProductImportError::row(
                $fila,
                $row,
                'Costo inválido.',
                'costo: '.$row['prod_valor_costo'],
            );
        }

        if (isset($row['prod_stock_real']) && $row['prod_stock_real'] !== '' && ! is_numeric($row['prod_stock_real'])) {
            return ProductImportError::row(
                $fila,
                $row,
                'Stock inválido.',
                'stock: '.$row['prod_stock_real'],
            );
        }

        return null;
    }

    /**
     * @param  array<string, string>  $rawRow
     * @param  array<string, string|null>|null  $columnMapping
     * @return array<string, string>
     */
    private function mapRow(array $rawRow, ?array $columnMapping = null): array
    {
        if ($columnMapping !== null) {
            return $this->mapRowWithColumnMapping($rawRow, $columnMapping);
        }

        $mapped = [];

        foreach ($rawRow as $header => $value) {
            if ($header === '_csv_line') {
                continue;
            }

            $key = self::HEADER_ALIASES[mb_strtolower(trim($header))] ?? null;
            if ($key !== null) {
                $mapped[$key] = trim((string) $value);
            }
        }

        return $this->finalizeMappedRow($mapped);
    }

    /**
     * @param  array<string, string>  $rawRow
     * @param  array<string, string|null>  $columnMapping
     * @return array<string, string>
     */
    private function mapRowWithColumnMapping(array $rawRow, array $columnMapping): array
    {
        $mapped = [];

        foreach (ProductImportColumnMapping::FIELDS as $field => $meta) {
            $source = trim((string) ($columnMapping[$field] ?? ''));

            if ($source === '') {
                continue;
            }

            $mapped[$meta['key']] = trim((string) ($rawRow[$source] ?? ''));
        }

        return $this->finalizeMappedRow($mapped);
    }

    /**
     * @param  array<string, string>  $mapped
     * @return array<string, string>
     */
    private function finalizeMappedRow(array $mapped): array
    {
        foreach (['prod_valor_costo', 'prod_imagen', 'prod_gramaje', 'prod_stock_real', 'prod_item_softland', 'slug', 'descripcion', 'peso_kg', 'activo', 'destacado'] as $optional) {
            $mapped[$optional] ??= '';
        }

        $mapped['prod_valor'] = $this->normalizeNumericField($mapped['prod_valor'] ?? '');
        $mapped['prod_valor_costo'] = $this->normalizeNumericField($mapped['prod_valor_costo'] ?? '');
        $mapped['prod_stock_real'] = $this->normalizeNumericField($mapped['prod_stock_real'] ?? '');

        return $mapped;
    }

    private function normalizeNumericField(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace([' ', "\xc2\xa0"], '', $value);

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $parts = explode(',', $value);
            if (count($parts) > 1 && strlen((string) end($parts)) === 3) {
                return str_replace(',', '', $value);
            }

            if (preg_match('/,\d{1,2}$/', $value)) {
                return $this->truncateDecimalString(str_replace(',', '.', $value));
            }
        }

        if (str_contains($value, '.') && ! str_contains($value, ',')) {
            $parts = explode('.', $value);
            if (count($parts) > 1) {
                if (strlen((string) end($parts)) === 3) {
                    return str_replace('.', '', $value);
                }

                if (count($parts) === 2 && strlen((string) end($parts)) <= 2) {
                    return $this->truncateDecimalString($value);
                }
            }
        }

        if (preg_match('/,\d{1,2}$/', $value) && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);

            return $this->truncateDecimalString($value);
        }

        return $value;
    }

    private function truncateDecimalString(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return explode('.', $value, 2)[0];
    }

    public function readPathAsUtf8(string $path): string
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            return '';
        }

        return $this->ensureUtf8($raw);
    }

    /**
     * @return list<array<string, string>>
     */
    public function parseCsvText(string $content): array
    {
        return $this->parseCsvContent($content);
    }

    /**
     * @return list<array<string, string>>
     */
    public function parseRowsFromPath(string $path, ?string $originalName = null): array
    {
        if (! is_file($path)) {
            return [];
        }

        $extension = ProductImportFileTypes::extensionFromName($originalName ?? basename($path));

        if (in_array($extension, ProductImportFileTypes::SPREADSHEET_EXTENSIONS, true)) {
            return app(ProductSpreadsheetReader::class)->parseFile($path);
        }

        $content = $this->readPathAsUtf8($path);

        return $this->parseCsvContent($content);
    }

    /**
     * Normaliza filas parseadas a texto CSV (;) para almacenamiento temporal.
     */
    public function rowsToCsvContent(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $headers = array_values(array_filter(
            array_keys($rows[0]),
            fn (string $header) => $header !== '_csv_line',
        ));

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ';');

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line, ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    public function readAndNormalizePath(string $path, ?string $originalName = null): string
    {
        $extension = ProductImportFileTypes::extensionFromName($originalName ?? $path);

        if (in_array($extension, ProductImportFileTypes::SPREADSHEET_EXTENSIONS, true)) {
            return $this->rowsToCsvContent($this->parseRowsFromPath($path, $originalName));
        }

        return $this->readPathAsUtf8($path);
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseRowsFromUploadedFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return [];
        }

        return $this->parseRowsFromPath($path, $file->getClientOriginalName());
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsvFromPathLimited(string $path, int $maxRows): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false || trim($firstLine) === '') {
                return [];
            }

            $firstLine = $this->ensureUtf8($firstLine);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
            $headers = array_map(
                fn (string $h) => mb_strtolower(trim($this->stripBom($h))),
                str_getcsv($this->stripBom($firstLine), $delimiter),
            );

            $rows = [];
            $physicalLine = 1;

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $physicalLine++;

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $row = ['_csv_line' => (string) $physicalLine];
                foreach ($headers as $columnIndex => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $row[$header] = trim((string) ($data[$columnIndex] ?? ''));
                }

                if (count($row) > 1) {
                    $rows[] = $row;
                }

                if (count($rows) >= $maxRows) {
                    break;
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsvFromPath(string $path): array
    {
        return $this->parseRowsFromPath($path);
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsvContent(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        if ($lines === []) {
            return [];
        }

        $firstLine = (string) ($lines[0] ?? '');
        if (trim($firstLine) === '') {
            return [];
        }

        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $headers = array_map(
            fn (string $h) => mb_strtolower(trim($this->stripBom($h))),
            str_getcsv($this->stripBom($firstLine), $delimiter),
        );

        $rows = [];

        foreach ($lines as $index => $line) {
            $physicalLine = $index + 1;

            if ($physicalLine === 1) {
                continue;
            }

            if (trim($line) === '') {
                continue;
            }

            $data = str_getcsv($line, $delimiter);
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $row = ['_csv_line' => $physicalLine];
            foreach ($headers as $headerIndex => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($data[$headerIndex] ?? ''));
            }

            if (count($row) > 1) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function stripBom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    private function ensureUtf8(string $value): string
    {
        $value = $this->stripBom($value);
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
            $converted = mb_convert_encoding($value, 'UTF-8', $encoding);
            if (mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return $value;
    }

    /**
     * @param  list<string|null>  $data
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  resource  $handle
     */
    private function seekCsvPhysicalLine($handle, string $delimiter, int $targetPhysicalLine): void
    {
        rewind($handle);
        fgets($handle);

        $currentLine = 1;

        while ($currentLine < $targetPhysicalLine - 1) {
            if (fgetcsv($handle, 0, $delimiter) === false) {
                return;
            }

            $currentLine++;
        }
    }
}
