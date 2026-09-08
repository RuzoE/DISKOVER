@extends('layouts.app')

@section('title', 'Panel')

@php $s = $data['stats']; @endphp

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Panel de administración</h1>
        <p class="page-header__subtitle">Visión general del sistema DSLE.</p>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="users" tone="blue" label="Usuarios"
                               :value="$s['users']" :hint="$s['users_active'].' activos · '.$s['users_suspended'].' suspendidos'" />
        <x-dashboard.stat-card icon="shield" tone="neutral" label="Roles" :value="$s['roles']" />
        <x-dashboard.stat-card icon="book" tone="green" label="Cursos" :value="$s['courses']" />
        <x-dashboard.stat-card icon="stack" tone="amber" label="Asignaturas" :value="$s['subjects']" />
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="cap" tone="neutral" label="Inscripciones activas" :value="$s['enrollments']" />
        <x-dashboard.stat-card icon="clipboard" tone="blue" label="Actividades" :value="$s['activities']" />
        <x-dashboard.stat-card icon="chart" tone="green" label="Calificaciones" :value="$s['grades']" />
    </div>

    <div class="dashboard-cols">
        <x-ui.card title="Usuarios por rol">
            <ul class="panel-list">
                @foreach ($data['roles'] as $role)
                    <li class="panel-list__item">
                        <span>{{ $role->name }}</span>
                        <span class="panel-list__meta">{{ $role->users_count }} usuario(s)</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card title="Últimos usuarios">
            <x-slot:actions>
                <x-ui.button :href="route('admin.users.index')" variant="ghost" class="btn--sm">Gestionar</x-ui.button>
            </x-slot:actions>
            <ul class="panel-list">
                @foreach ($data['recent_users'] as $u)
                    <li class="panel-list__item">
                        <a href="{{ route('admin.users.show', $u) }}" class="link">{{ $u->name }}</a>
                        <span class="panel-list__meta">
                            {{ $u->roles->pluck('name')->implode(', ') ?: 'sin rol' }} ·
                            {{ $u->created_at?->format('d/m/Y') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>

    <x-ui.card title="Actividad reciente del sistema">
        <x-analytics.timeline :events="$data['history']" />
    </x-ui.card>
@endsection
