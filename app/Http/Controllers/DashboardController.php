<?php

namespace App\Http\Controllers;

use App\Enums\RoleSlug;
use App\Services\Analytics\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de inicio. Deriva al dashboard específico según el rol prioritario
 * del usuario (admin > coordinación > docente > estudiante).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('roles');

        return match ($this->dashboard->primaryRole($user)) {
            RoleSlug::Admin => view('admin.dashboard.index', [
                'user' => $user,
                'data' => $this->dashboard->forAdmin($user),
            ]),
            RoleSlug::Coordinator => view('coordinator.dashboard.index', [
                'user' => $user,
                'data' => $this->dashboard->forCoordinator($user),
            ]),
            RoleSlug::Teacher => view('teacher.dashboard.index', [
                'user' => $user,
                'data' => $this->dashboard->forTeacher($user),
            ]),
            RoleSlug::Student => view('student.dashboard.index', [
                'user' => $user,
                'data' => $this->dashboard->forStudent($user),
            ]),
            default => view('dashboard', ['user' => $user, 'roles' => $user->roles]),
        };
    }
}
