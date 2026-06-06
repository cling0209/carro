<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductChunkUploadService
{
    public const MAX_CHUNK_BYTES = 1048576;

    public const MAX_TOTAL_BYTES = 52428800;

    /**
     * @return array{created: int, updated: int, reactivated: int, skipped: int, errors: list<string>}
     */
    public function storeChunk(
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        string $originalName,
        UploadedFile $chunk,
        int $userId,
    ): array {
        $this->assertValidUploadId($uploadId);
        $this->assertCsvFileName($originalName);

        if ($totalChunks < 1 || $chunkIndex < 0 || $chunkIndex >= $totalChunks) {
            throw new \InvalidArgumentException('Índice de chunk inválido.');
        }

        if ($chunk->getSize() > self::MAX_CHUNK_BYTES) {
            throw new \InvalidArgumentException('El fragmento supera el tamaño máximo permitido.');
        }

        $estimatedSize = $totalChunks * self::MAX_CHUNK_BYTES;

        if ($estimatedSize > self::MAX_TOTAL_BYTES) {
            throw new \InvalidArgumentException('El archivo completo supera el tamaño máximo permitido.');
        }

        $dir = $this->uploadDirectory($uploadId);
        File::ensureDirectoryExists($dir);

        if ($chunkIndex === 0) {
            File::cleanDirectory($dir);
            File::put($dir.'/meta.json', json_encode([
                'user_id' => $userId,
                'original_name' => $originalName,
                'total_chunks' => $totalChunks,
                'created_at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR));
        }

        $meta = $this->readMeta($uploadId);

        if ($meta['user_id'] !== $userId) {
            throw new \InvalidArgumentException('No autorizado para continuar esta carga.');
        }

        if ($meta['total_chunks'] !== $totalChunks) {
            throw new \InvalidArgumentException('La cantidad total de fragmentos no coincide.');
        }

        $chunk->move($dir, $this->chunkFilename($chunkIndex));

        if ($chunkIndex !== $totalChunks - 1) {
            return [
                'created' => 0,
                'updated' => 0,
                'reactivated' => 0,
                'skipped' => 0,
                'errors' => [],
            ];
        }

        $mergedPath = $this->mergeChunks($uploadId, $totalChunks);

        try {
            $uploaded = new UploadedFile(
                $mergedPath,
                $meta['original_name'],
                'text/csv',
                null,
                true
            );

            return app(ProductImportService::class)->importFromUploadedFile($uploaded);
        } finally {
            $this->cleanup($uploadId);
            if (File::exists($mergedPath)) {
                File::delete($mergedPath);
            }
        }
    }

    protected function mergeChunks(string $uploadId, int $totalChunks): string
    {
        $dir = $this->uploadDirectory($uploadId);
        File::ensureDirectoryExists(storage_path('app/imports/merged'));

        $mergedPath = storage_path('app/imports/merged/'.$uploadId.'.csv');
        $output = fopen($mergedPath, 'wb');

        if ($output === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal.');
        }

        for ($index = 0; $index < $totalChunks; $index++) {
            $partPath = $dir.'/'.$this->chunkFilename($index);

            if (! File::exists($partPath)) {
                fclose($output);
                File::delete($mergedPath);
                throw new \InvalidArgumentException('Falta el fragmento '.($index + 1).' de '.$totalChunks.'.');
            }

            $input = fopen($partPath, 'rb');

            if ($input === false) {
                fclose($output);
                File::delete($mergedPath);
                throw new \RuntimeException('No se pudo leer un fragmento de la carga.');
            }

            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        fclose($output);

        return $mergedPath;
    }

    /**
     * @return array{user_id: int, original_name: string, total_chunks: int, created_at: string}
     */
    protected function readMeta(string $uploadId): array
    {
        $metaPath = $this->uploadDirectory($uploadId).'/meta.json';

        if (! File::exists($metaPath)) {
            throw new \InvalidArgumentException('La carga no fue iniciada correctamente.');
        }

        $meta = json_decode(File::get($metaPath), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($meta) || ! isset($meta['user_id'], $meta['original_name'], $meta['total_chunks'])) {
            throw new \InvalidArgumentException('Metadatos de carga inválidos.');
        }

        return $meta;
    }

    protected function cleanup(string $uploadId): void
    {
        $dir = $this->uploadDirectory($uploadId);

        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    protected function uploadDirectory(string $uploadId): string
    {
        return storage_path('app/imports/chunks/'.$uploadId);
    }

    protected function chunkFilename(int $index): string
    {
        return 'part-'.str_pad((string) $index, 6, '0', STR_PAD_LEFT);
    }

    protected function assertValidUploadId(string $uploadId): void
    {
        if (! Str::isUuid($uploadId)) {
            throw new \InvalidArgumentException('Identificador de carga inválido.');
        }
    }

    protected function assertCsvFileName(string $originalName): void
    {
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, ['csv', 'txt'], true)) {
            throw new \InvalidArgumentException('Solo se permiten archivos CSV.');
        }
    }
}
