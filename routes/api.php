<?php

use App\Http\Controllers\Immersive\Api\SessionController as ImmersiveSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — DSLE
|--------------------------------------------------------------------------
|
| Todas las rutas se agrupan bajo /api/v1 para permitir versiones futuras.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        /*
        | Experiencias inmersivas (Fase 9). El cliente (Unity) se autentica con
        | el `launch_token` de la sesión, no con sesión de usuario. Ver ADR-0012.
        */
        Route::prefix('immersive')
            ->name('immersive.')
            ->middleware('throttle:immersive')
            ->group(function (): void {
                Route::get('sessions/{token}', [ImmersiveSessionController::class, 'show'])->name('sessions.show');
                Route::post('sessions/{token}/complete', [ImmersiveSessionController::class, 'complete'])->name('sessions.complete');
                Route::post('sessions/{token}/abandon', [ImmersiveSessionController::class, 'abandon'])->name('sessions.abandon');
            });
    });
