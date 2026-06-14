<?php

namespace App\Services;

use App\Models\ProductImportErrorLog;
use App\Models\ProductImportRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportRunService
{
    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}
     */
    public function beginRun(string $usuario, string $archivo): ProductImportRun
    {
        return DB::transaction(function () use ($usuario, $archivo) {
            ProductImportRun::query()->delete();

            return ProductImportRun::query()->create([
                'usuario' => $usuario,
                'archivo' => $archivo,
                'estado' => ProductImportRun::ESTADO_OK,
            ]);
        });
    }

    /**
     * @param  array{created: int, updated: int, skipped: int, errors: list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>}  $result
     */
    public function completeRun(ProductImportRun $run, array $result): ProductImportRun
    {
        $totalErrores = count($result['errors']);
        $importoAlgo = $result['created'] + $result['updated'] > 0;

        $estado = ProductImportRun::ESTADO_OK;
        if ($totalErrores > 0 && ! $importoAlgo) {
            $estado = ProductImportRun::ESTADO_FALLIDO;
        } elseif ($totalErrores > 0) {
            $estado = ProductImportRun::ESTADO_ERRORES;
        }

        $run->update([
            'creados' => $result['created'],
            'actualizados' => $result['updated'],
            'omitidos' => $result['skipped'],
            'total_errores' => $totalErrores,
            'estado' => $estado,
            'finished_at' => now(),
        ]);

        if ($totalErrores > 0) {
            $this->persistErrors($run, $result['errors']);
        }

        return $run->fresh();
    }

    /**
     * @param  list<array{fila: int|null, codigo: string, nombre: string, familia: string, mensaje: string, detalle: string|null}>  $errors
     */
    public function persistErrors(ProductImportRun $run, array $errors): void
    {
        foreach (array_chunk($errors, 500) as $chunk) {
            $rows = [];

            foreach ($chunk as $error) {
                $rows[] = [
                    'run_id' => $run->id,
                    'fila' => $error['fila'],
                    'codigo' => $error['codigo'] !== '' ? $error['codigo'] : null,
                    'nombre' => $error['nombre'] !== '' ? mb_substr($error['nombre'], 0, 255) : null,
                    'familia' => $error['familia'] !== '' ? $error['familia'] : null,
                    'mensaje' => mb_substr($error['mensaje'], 0, 255),
                    'detalle' => $error['detalle'] !== null && $error['detalle'] !== ''
                        ? mb_substr($error['detalle'], 0, 255)
                        : null,
                ];
            }

            ProductImportErrorLog::query()->insert($rows);
        }
    }

    public function latestRun(): ?ProductImportRun
    {
        return ProductImportRun::query()->latest('id')->first();
    }

    public function findRun(int $runId): ProductImportRun
    {
        return ProductImportRun::query()->findOrFail($runId);
    }

    public function paginateErrors(ProductImportRun $run, int $perPage = 50): LengthAwarePaginator
    {
        return ProductImportErrorLog::query()
            ->where('run_id', $run->id)
            ->orderBy('fila')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportErrorsCsvResponse(ProductImportRun $run): StreamedResponse
    {
        return response()->streamDownload(function () use ($run) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['fila', 'codigo', 'nombre', 'familia', 'error', 'detalle'], ';');

            ProductImportErrorLog::query()
                ->where('run_id', $run->id)
                ->orderBy('fila')
                ->orderBy('id')
                ->chunk(500, function ($errores) use ($handle) {
                    foreach ($errores as $error) {
                        fputcsv($handle, [
                            $error->fila ?? '',
                            $error->codigo ?? '',
                            $error->nombre ?? '',
                            $error->familia ?? '',
                            $error->mensaje,
                            $error->detalle ?? '',
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'errores_importacion_'.$run->id.'_'.now()->format('Y-m-d_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function redirectUrlForRun(ProductImportRun $run): string
    {
        if ($run->tieneErrores()) {
            return route('admin.products.import.errores', ['run' => $run->id]);
        }

        return route('admin.products.import.resultado', ['run' => $run->id]);
    }
}
