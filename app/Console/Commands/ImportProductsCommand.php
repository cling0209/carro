<?php

namespace App\Console\Commands;

use App\Services\ProductBulkCopyImportService;
use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    protected $signature = 'products:import
                            {file : Ruta al archivo CSV}
                            {--dry-run : Validar y mostrar resumen sin escribir en la base de datos}';

    protected $description = 'Importa productos desde CSV usando COPY + staging (rápido, ideal para cargas masivas)';

    public function handle(ProductBulkCopyImportService $importService): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error('No se encontró el archivo: '.$path);

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se escribirá en la base de datos.');
        }

        $this->info('Importando: '.$path);
        $startedAt = microtime(true);

        try {
            $result = $importService->importFromPath($path, $dryRun);
        } catch (\Throwable $exception) {
            $this->error('Importación fallida: '.$exception->getMessage());

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $startedAt, 2);

        $this->newLine();
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Creados', $result['created']],
                ['Actualizados', $result['updated']],
                ['Reactivados', $result['reactivated']],
                ['Omitidos', $result['skipped']],
                ['Tiempo (s)', $dryRun ? '—' : $elapsed],
            ],
        );

        if ($result['errors'] !== []) {
            $this->warn('Errores ('.count($result['errors']).'):');

            foreach (array_slice($result['errors'], 0, 20) as $error) {
                $this->line(' - '.$error);
            }

            if (count($result['errors']) > 20) {
                $this->line(' ... y '.(count($result['errors']) - 20).' más.');
            }
        }

        if ($dryRun) {
            $this->info('Dry-run completado.');

            return $result['skipped'] > 0 && $result['created'] + $result['updated'] + $result['reactivated'] === 0
                ? self::FAILURE
                : self::SUCCESS;
        }

        $this->info('Importación completada en '.$elapsed.' s.');

        return $result['skipped'] > 0 && $result['created'] + $result['updated'] + $result['reactivated'] === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
