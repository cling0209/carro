<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catálogo de productos: administradores y ejecutivos (imagen).
 */
class EnsureCatalogAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isEjecutivo())) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'data' => null,
                    'meta' => (object) [],
                    'errors' => ['message' => 'Acceso no autorizado.'],
                ], 403);
            }

            return redirect()
                ->route($user?->adminHomeRoute() ?? 'admin.login')
                ->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
