<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Imágenes de productos (URL externa / R2)
    |--------------------------------------------------------------------------
    |
    | URL = {image_base_url}/{familia}/{image_filename}
    | Al subir desde admin, el archivo siempre se guarda como {sku}.jpg
    | (PNG/WebP/GIF se convierten a JPG).
    |
    */

    /*
    | Prioridad lectura: PRODUCT_IMAGE_BASE_URL → R2_PUBLIC_URL + prefijo
    */
    'image_base_url' => env('PRODUCT_IMAGE_BASE_URL') ?: (
        filled($r2Public = env('R2_PUBLIC_URL'))
            ? rtrim($r2Public, '/').'/'.trim(env('R2_IMAGE_PREFIX', 'productos'), '/')
            : null
    ),

    'image_fallback_url' => env(
        'PRODUCT_IMAGE_FALLBACK_URL',
        '/images/no-image.svg'
    ),

    'storage_disk' => env('PRODUCT_STORAGE_DISK', 'r2'),

    'r2_prefix' => env('R2_IMAGE_PREFIX', 'productos'),

    /*
    | Al subir imagen: se redimensiona a un cuadrado con fondo blanco (contain, sin ampliar).
    */
    'image_listing_size' => (int) env('PRODUCT_IMAGE_LISTING_SIZE', 400),

    'image_jpeg_quality' => (int) env('PRODUCT_IMAGE_JPEG_QUALITY', 85),

    'import' => [
        'background' => filter_var(env('PRODUCT_IMPORT_BACKGROUND', true), FILTER_VALIDATE_BOOL),
        'auto_create_categories' => filter_var(env('PRODUCT_IMPORT_AUTO_CREATE_CATEGORIES', true), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Marcas preferidas en búsqueda
    |--------------------------------------------------------------------------
    |
    | En resultados del buscador, productos que mencionen estas marcas
    | (nombre, descripción o atributo "Marca") aparecen primero.
    | Separar varias con coma en PRODUCT_PREFERRED_BRANDS.
    |
    */

    'preferred_brands' => array_values(array_filter(array_map(
        static fn (string $brand): string => trim($brand),
        explode(',', (string) env('PRODUCT_PREFERRED_BRANDS', 'Reysol'))
    ))),

];
