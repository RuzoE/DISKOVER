@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Hola, {{ $user->name }}</h1>
        <p class="page-header__subtitle">Bienvenido a DISKOVER Smart Learning Ecosystem.</p>
    </div>

    <x-ui.card title="Tu cuenta">
        <dl class="detail-list">
            <div class="detail-list__row">
                <dt>Correo</dt>
                <dd>{{ $user->email }}</dd>
            </div>
            <div class="detail-list__row">
                <dt>Estado</dt>
                <dd>
                    <x-ui.badge :color="$user->status->value === 'active' ? 'green' : 'amber'">
                        {{ $user->status->label() }}
                    </x-ui.badge>
                </dd>
            </div>
            <div class="detail-list__row">
                <dt>Roles</dt>
                <dd>
                    @forelse ($roles as $role)
                        <x-ui.badge color="blue">{{ $role->name }}</x-ui.badge>
                    @empty
                        <span class="text-muted">Sin roles asignados</span>
                    @endforelse
                </dd>
            </div>
            <div class="detail-list__row">
                <dt>Último acceso</dt>
                <dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Accesos rápidos">
        <div class="u-flex u-gap-3" style="flex-wrap: wrap;">
            @can('viewAny', App\Models\Course::class)
                <x-ui.button :href="route('coordinator.courses.index')" variant="secondary" class="btn--sm">Cursos</x-ui.button>
            @endcan
            @if ($user->hasRole(App\Enums\RoleSlug::Teacher) || $user->isAdmin())
                <x-ui.button :href="route('teacher.subjects.index')" variant="secondary" class="btn--sm">Mis asignaturas</x-ui.button>
            @endif
            @if ($user->hasRole(App\Enums\RoleSlug::Student) || $user->isAdmin())
                <x-ui.button :href="route('student.courses.index')" variant="secondary" class="btn--sm">Mis cursos</x-ui.button>
                <x-ui.button :href="route('student.profile.show')" variant="secondary" class="btn--sm">Mi progreso</x-ui.button>
            @endif
            @can('viewAny', App\Models\User::class)
                <x-ui.button :href="route('admin.users.index')" variant="secondary" class="btn--sm">Usuarios</x-ui.button>
            @endcan
        </div>
    </x-ui.card>

    <p class="text-muted u-mt-4">
        Los paneles específicos por rol se incorporarán en la Fase 5.
    </p>
@endsection
