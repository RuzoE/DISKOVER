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

    <p class="text-muted u-mt-4">
        Los paneles específicos por rol se incorporarán en la Fase 5.
    </p>
@endsection
