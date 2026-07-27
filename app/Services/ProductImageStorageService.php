<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductImageStorageService
{
    public function __construct(
        protected ProductImageProcessor $processor,
    ) {}

    public function disk(): string
    {
        return (string) config('products.storage_disk', 'r2');
    }

    public function isConfigured(): bool
    {
        $disk = $this->disk();

        return (bool) config("filesystems.disks.{$disk}.bucket")
            && (bool) config("filesystems.disks.{$disk}.key")
            && filled(config('products.image_base_url'));
    }

    public function objectKey(string $familia, string $filename): string
    {
        $prefix = trim((string) config('products.r2_prefix', 'productos'), '/');
        $folder = trim($familia) !== '' ? trim($familia) : 'OTRO';
        $file = ltrim(trim($filename), '/');

        return $prefix.'/'.$folder.'/'.$file;
    }

    /**
     * Sube la imagen convertida a JPG con nombre fijo {sku}.jpg.
     */
    public function upload(UploadedFile $file, string $familia, string $sku): string
    {
        $processed = $this->processor->processUploadedFile($file);

        if ($processed === null) {
            throw ValidationException::withMessages([
                'imagen' => 'No se pudo procesar la imagen. Use JPG, PNG, WebP o GIF.',
            ]);
        }

        $filename = $this->buildFilename($sku);
        $key = $this->objectKey($familia, $filename);

        Storage::disk($this->disk())->put($key, $processed['contents'], [
            'visibility' => 'public',
            'CacheControl' => 'public, max-age=31536000, immutable',
            'ContentType' => $processed['mime'],
        ]);

        return $filename;
    }

    public function buildFilename(string $sku): string
    {
        $safeSku = preg_replace('/[^a-zA-Z0-9._-]+/', '_', trim($sku)) ?: 'producto';

        return $safeSku.'.jpg';
    }

    public function canUpload(): bool
    {
        $disk = $this->disk();

        return (bool) config("filesystems.disks.{$disk}.bucket")
            && (bool) config("filesystems.disks.{$disk}.key")
            && (bool) config("filesystems.disks.{$disk}.secret");
    }

    public function publicUrl(?string $familia, ?string $filename): ?string
    {
        $base = rtrim((string) config('products.image_base_url'), '/');
        $folder = trim((string) $familia);
        $file = trim((string) $filename);

        if ($base === '' || $file === '' || $folder === '') {
            return null;
        }

        return $base.'/'.trim($folder, '/').'/'.ltrim($file, '/');
    }
}
