@extends('layouts.guest')

@section('title', 'Nueva contraseña')

@section('content')
    <h1 class="guest-card__title">Establecer nueva contraseña</h1>

    <form method="POST" action="{{ route('password.store') }}" class="form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.input
            name="email"
            type="email"
            label="Correo electrónico"
            :value="$email"
            autocomplete="username"
            required
        />

        <x-ui.input
            name="password"
            type="password"
            label="Nueva contraseña"
            autocomplete="new-password"
            required
        />

        <x-ui.input
            name="password_confirmation"
            type="password"
            label="Confirmar contraseña"
            autocomplete="new-password"
            required
        />

        <div class="form__actions">
            <x-ui.button type="submit" variant="primary">Guardar contraseña</x-ui.button>
        </div>
    </form>
@endsection
