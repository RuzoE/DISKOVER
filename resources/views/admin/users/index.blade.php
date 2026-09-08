@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Usuarios</h1>
            <p class="page-header__subtitle">Gestión de cuentas y asignación de roles.</p>
        </div>
        @can('create', App\Models\User::class)
            <x-ui.button :href="route('admin.users.create')" variant="primary">Nuevo usuario</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="filters">
            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Buscar por nombre o correo"
                class="field__control"
                aria-label="Buscar usuarios"
            >
            <select name="status" class="field__control" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
        </form>

        @if ($users->isEmpty())
            <x-tables.empty-state message="No se encontraron usuarios con esos criterios." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th class="table__actions-col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <x-ui.badge color="blue">{{ $role->name }}</x-ui.badge>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>
                                    <x-ui.badge :color="$user->status->value === 'active' ? 'green' : ($user->status->value === 'suspended' ? 'red' : 'amber')">
                                        {{ $user->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="table__actions">
                                    @can('update', $user)
                                        <a href="{{ route('admin.users.edit', $user) }}" class="link">Editar</a>
                                    @endcan
                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              data-confirm="¿Eliminar al usuario {{ $user->name }}?">
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

            <div class="u-mt-4">{{ $users->links() }}</div>
        @endif
    </x-ui.card>
@endsection
