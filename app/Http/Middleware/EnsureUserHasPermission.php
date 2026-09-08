<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a usuarios que posean TODOS los permisos indicados.
 *
 * Uso:  ->middleware('permission:users.view')
 *       ->middleware('permission:users.view,users.update')
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        foreach ($permissions as $permission) {
            if ($user === null || ! $user->hasPermission($permission)) {
                abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para realizar esta acción.');
            }
        }

        return $next($request);
    }
}
