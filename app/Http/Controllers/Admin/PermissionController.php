<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Los permisos son un catálogo del sistema: se consultan pero no se crean ni
 * editan desde la interfaz. Se asignan a los roles desde RoleController.
 */
class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('viewAny', Role::class), 403);

        return view('admin.permissions.index', [
            'permissionGroups' => Permission::orderBy('group')->orderBy('name')->get()->groupBy('group'),
        ]);
    }
}
