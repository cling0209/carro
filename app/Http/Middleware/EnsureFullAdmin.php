<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo administradores completos (no bodega).
 */
class EnsureFullAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
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
