<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de cualquier usuario cuya cuenta haya dejado de estar
 * activa (inactivada o suspendida) mientras la sesión seguía abierta.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(Response::HTTP_FORBIDDEN, 'Tu cuenta no está activa. Contacta con la administración.');
        }

        return $next($request);
    }
}
