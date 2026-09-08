@extends('layouts.app')

@section('title', 'Detalle de rol')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $role->name }}</h1>
            <p class="page-header__subtitle"><code>{{ $role->slug }}</code></p>
        </div>
        @can('update', $role)
            <x-ui.button :href="route('admin.roles.edit', $role)" variant="primary">Editar</x-ui.button>
        @endcan
    </div>

    <x-ui.card title="Permisos asignados">
        <div class="checkbox-grid">
            @forelse ($role->permissions as $permission)
                <x-ui.badge>{{ $permission->name }}</x-ui.badge>
            @empty
                <span class="text-muted">Sin permisos.</span>
            @endforelse
        </div>
    </x-ui.card>
@endsection
