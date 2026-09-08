@extends('layouts.guest')

@section('title', 'Recuperar contraseña')

@section('content')
    <h1 class="guest-card__title">Recuperar contraseña</h1>
    <p class="guest-card__lead">
        Introduce tu correo y te enviaremos un enlace para establecer una nueva contraseña.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="form">
        @csrf

        <x-ui.input
            name="email"
            type="email"
            label="Correo electrónico"
            autocomplete="username"
            required
            autofocus
        />

        <div class="form__actions">
            <x-ui.button type="submit" variant="primary">Enviar enlace</x-ui.button>
            <a href="{{ route('login') }}" class="link">Volver a iniciar sesión</a>
        </div>
    </form>
@endsection
