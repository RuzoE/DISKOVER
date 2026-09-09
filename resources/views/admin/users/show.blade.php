@extends('layouts.app')

@section('title', 'Detalle de usuario')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $user->name }}</h1>
            <p class="page-header__subtitle">{{ $user->email }}</p>
        </div>
        <div class="u-flex u-gap-3">
            @if ($user->hasRole(App\Enums\RoleSlug::Student))
                <x-ui.button :href="route('reports.transcript', $user)" variant="ghost">Expediente</x-ui.button>
            @endif
            @can('update', $user)
                <x-ui.button :href="route('admin.users.edit', $user)" variant="primary">Editar</x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card title="Información">
        <dl class="detail-list">
            <div class="detail-list__row">
                <dt>Estado</dt>
                <dd>
                    <x-ui.badge :color="$user->status->value === 'active' ? 'green' : 'amber'">
                        {{ $user->status->label() }}
                    </x-ui.badge>
                </dd>
            </div>
            <div class="detail-list__row">
                <dt>Último acceso</dt>
                <dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div class="detail-list__row">
                <dt>Alta</dt>
                <dd>{{ $user->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Roles y permisos efectivos">
        @forelse ($user->roles as $role)
            <div class="u-mb-4">
                <p><strong>{{ $role->name }}</strong></p>
                <div class="checkbox-grid">
                    @forelse ($role->permissions as $permission)
                        <x-ui.badge>{{ $permission->name }}</x-ui.badge>
                    @empty
                        <span class="text-muted">Este rol no tiene permisos asignados.</span>
                    @endforelse
                </div>
            </div>
        @empty
            <p class="text-muted">El usuario no tiene roles asignados.</p>
        @endforelse
    </x-ui.card>
@endsection
