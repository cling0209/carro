<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Imágenes de productos (URL externa)
    |--------------------------------------------------------------------------
    |
    | URL = {image_base_url}/{familia}/{image_filename}
    | familia e image_filename se definen por producto en admin o carga masiva.
    |
    | Ejemplo:
    | https://www.romulo.cl/allproducts/imagenes/productos/LIB/90503.jpg
    |
    */

    'image_base_url' => env('PRODUCT_IMAGE_BASE_URL'),

    'image_fallback_url' => env(
        'PRODUCT_IMAGE_FALLBACK_URL',
        '/images/no-image.svg'
    ),

    'import' => [
        'background' => filter_var(env('PRODUCT_IMPORT_BACKGROUND', true), FILTER_VALIDATE_BOOL),
        'auto_create_categories' => filter_var(env('PRODUCT_IMPORT_AUTO_CREATE_CATEGORIES', true), FILTER_VALIDATE_BOOL),
    ],

];
