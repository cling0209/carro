<?php

namespace App\Support;

use App\Services\ProductImportLockService;
use App\Services\ProductImportProgressService;

class ProductImportHeartbeat
{
    private static ?string $uploadId = null;

    private static int $rowsSincePulse = 0;

    public static function bind(?string $uploadId): void
    {
        self::$uploadId = $uploadId !== null && $uploadId !== '' ? $uploadId : null;
        self::$rowsSincePulse = 0;
    }

    public static function clear(): void
    {
        self::$uploadId = null;
        self::$rowsSincePulse = 0;
    }

    public static function rowProcessed(int $processedInBatch, int $totalInBatch, array $result): void
    {
        if (self::$uploadId === null) {
            return;
        }

        self::$rowsSincePulse++;

        if (self::$rowsSincePulse < 10 && $processedInBatch < $totalInBatch) {
            return;
        }

        self::$rowsSincePulse = 0;

        $created = (int) ($result['created'] ?? 0);
        $updated = (int) ($result['updated'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);

        app(ProductImportProgressService::class)->pulse(
            self::$uploadId,
            sprintf(
                'Grabando filas (%d de %d) — creados: %s, actualizados: %s, omitidos: %s',
                min($processedInBatch, $totalInBatch),
                max(1, $totalInBatch),
                number_format($created, 0, '', '.'),
                number_format($updated, 0, '', '.'),
                number_format($skipped, 0, '', '.'),
            ),
        );

        app(ProductImportLockService::class)->touch(self::$uploadId);
    }
}
