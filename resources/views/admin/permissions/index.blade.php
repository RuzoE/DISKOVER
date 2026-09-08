@extends('layouts.app')

@section('title', 'Permisos')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Catálogo de permisos</h1>
            <p class="page-header__subtitle">
                Los permisos son fijos del sistema. Se conceden a los usuarios a través de los roles.
            </p>
        </div>
    </div>

    @foreach ($permissionGroups as $group => $permissions)
        <x-ui.card :title="ucfirst($group)">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Permiso</th>
                            <th>Identificador</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td>{{ $permission->name }}</td>
                                <td><code>{{ $permission->slug }}</code></td>
                                <td>{{ $permission->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endforeach
@endsection
