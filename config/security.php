<?php

$imageBaseUrl = env('PRODUCT_IMAGE_BASE_URL');
$imageHost = is_string($imageBaseUrl) && $imageBaseUrl !== ''
    ? parse_url($imageBaseUrl, PHP_URL_HOST)
    : null;

$fallbackUrl = env('PRODUCT_IMAGE_FALLBACK_URL', '/images/no-image.svg');
$fallbackHost = is_string($fallbackUrl) && str_starts_with($fallbackUrl, 'http')
    ? parse_url($fallbackUrl, PHP_URL_HOST)
    : null;

$extraImageHosts = array_values(array_filter(array_unique([
    $imageHost,
    $fallbackHost,
    'www.romulo.cl',
    'picsum.photos',
])));

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Directivas base para mitigar XSS. Se complementan con hosts de imágenes
    | externos definidos en PRODUCT_IMAGE_*.
    |
    */

    'csp' => [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self' https://webpay3gint.transbank.cl https://webpay.transbank.cl",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'",
        "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'",
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
        'img-src \'self\' data: blob: '.implode(' ', array_map(
            fn (string $host) => 'https://'.$host,
            $extraImageHosts,
        )),
        "connect-src 'self'",
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Transport Security
    |--------------------------------------------------------------------------
    |
    | Solo se envía cuando la petición es HTTPS (Render / producción).
    |
    */

    'hsts' => 'max-age=31536000; includeSubDomains',

];
