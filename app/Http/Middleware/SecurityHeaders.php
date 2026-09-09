<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Añade cabeceras de seguridad HTTP a todas las respuestas web. No se define una
 * CSP estricta a propósito: rompería los manejadores en línea del panel y el
 * iframe de Unity de las experiencias inmersivas (ver ADR-0014).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        if ($request->secure()) {
            $maxAge = (int) config('dsle.security.hsts_max_age', 31536000);
            $headers['Strict-Transport-Security'] = "max-age={$maxAge}; includeSubDomains";
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
