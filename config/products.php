<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Imágenes de productos (URL externa)
    |--------------------------------------------------------------------------
    |
    | URL = {image_base_url}/{familia}/{filename}
    | filename = str_replace('{codigo}', sku, image_filename_pattern)
    |
    | Ejemplo:
    | https://www.romulo.cl/allproducts/imagenes/productos/electronica/AUD-001_medium.jpg
    |
    */

    'image_base_url' => env('PRODUCT_IMAGE_BASE_URL'),

    'image_filename_pattern' => env('PRODUCT_IMAGE_FILENAME_PATTERN', '{codigo}_medium.jpg'),

    'image_fallback_url' => env(
        'PRODUCT_IMAGE_FALLBACK_URL',
        '/images/no-image.svg'
    ),

];
