<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', implode('; ', config('security.csp')));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', config('security.hsts'));
        }

        if ($this->shouldPreventCaching($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    protected function shouldPreventCaching(Request $request): bool
    {
        return $request->is('admin', 'admin/*')
            || $request->is('checkout', 'checkout/*')
            || $request->is('cuenta/*')
            || $request->is('api/v1/cart*')
            || $request->is('api/v1/orders*')
            || $request->is('api/v1/auth/*')
            || $request->is('api/v1/payments/*')
            || $request->is('api/documentation*');
    }
}
