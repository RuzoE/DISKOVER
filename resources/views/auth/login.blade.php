@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <h1 class="guest-card__title">Iniciar sesión</h1>

    @if ($errors->any())
        <x-ui.alert type="danger" class="u-mb-4">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="form">
        @csrf

        <x-ui.input
            name="email"
            type="email"
            label="Correo electrónico"
            autocomplete="username"
            required
            autofocus
        />

        <x-ui.input
            name="password"
            type="password"
            label="Contraseña"
            autocomplete="current-password"
            required
        />

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span>Mantener la sesión iniciada</span>
        </label>

        <div class="form__actions">
            <x-ui.button type="submit" variant="primary">Entrar</x-ui.button>
            <a href="{{ route('password.request') }}" class="link">¿Olvidaste tu contraseña?</a>
        </div>
    </form>
@endsection
