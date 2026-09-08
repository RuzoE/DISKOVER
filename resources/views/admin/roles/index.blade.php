@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Roles</h1>
            <p class="page-header__subtitle">Conjuntos de permisos asignables a los usuarios.</p>
        </div>
        @can('create', App\Models\Role::class)
            <x-ui.button :href="route('admin.roles.create')" variant="primary">Nuevo rol</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Identificador</th>
                        <th>Permisos</th>
                        <th>Usuarios</th>
                        <th>Tipo</th>
                        <th class="table__actions-col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                @if ($role->is_system)
                                    <x-ui.badge color="neutral">Sistema</x-ui.badge>
                                @else
                                    <x-ui.badge color="blue">Personalizado</x-ui.badge>
                                @endif
                            </td>
                            <td class="table__actions">
                                @can('update', $role)
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="link">Editar</a>
                                @endcan
                                @can('delete', $role)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                          data-confirm="¿Eliminar el rol {{ $role->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link link--danger">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
