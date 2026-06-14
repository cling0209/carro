<?php

namespace App\Jobs;

use App\Services\ProductImportJobService;
use App\Services\ProductImportLockService;
use App\Services\ProductImportProgressService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessProductImportJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * @param  array<string, string|null>|null  $columnMapping
     */
    public function __construct(
        public string $uploadId,
        public int $userId,
        public string $mode = 'template',
        public ?array $columnMapping = null,
    ) {}

    public function uniqueId(): string
    {
        return $this->uploadId;
    }

    public function handle(ProductImportJobService $importJob): void
    {
        $importJob->runBackgroundImport(
            $this->uploadId,
            $this->userId,
            $this->mode,
            $this->columnMapping,
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessProductImportJob failed', [
            'upload_id' => $this->uploadId,
            'message' => $exception?->getMessage(),
        ]);

        app(ProductImportProgressService::class)->fail(
            $this->uploadId,
            $exception?->getMessage() ?: 'La importación en segundo plano falló.',
        );
        app(ProductImportLockService::class)->release($this->uploadId);
    }
}
