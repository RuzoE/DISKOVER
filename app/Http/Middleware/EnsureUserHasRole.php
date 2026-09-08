<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a usuarios que posean al menos uno de los roles indicados.
 *
 * Uso:  ->middleware('role:admin')
 *       ->middleware('role:admin,coordinator')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes el rol necesario para acceder a esta sección.');
        }

        return $next($request);
    }
}
