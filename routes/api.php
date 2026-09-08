<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — DSLE
|--------------------------------------------------------------------------
|
| Todas las rutas de la API se agrupan bajo un prefijo de versión (/api/v1)
| para permitir la coexistencia de futuras versiones sin romper clientes
| existentes. Cada módulo (Academic, Analytics, AI, Recommendations,
| Immersive, Reports) registrará aquí sus rutas en su fase correspondiente.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        // Las rutas de cada módulo se añadirán en su fase.
        // Ejemplo de contrato objetivo:
        //   GET /api/v1/courses
        //   GET /api/v1/students/{student}/progress
        //   GET /api/v1/analytics/students/{student}
    });
