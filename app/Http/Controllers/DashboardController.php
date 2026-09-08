<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de aterrizaje tras el inicio de sesión.
 *
 * Es un marcador de posición: la Fase 5 sustituirá esta vista por
 * dashboards específicos por rol (estudiante, docente, administrador,
 * coordinación).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('roles');

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->roles,
        ]);
    }
}
