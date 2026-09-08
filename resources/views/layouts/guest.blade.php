<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Acceso') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-body">
    <main class="guest-shell">
        <div class="guest-card">
            <div class="guest-card__brand">
                <span class="guest-card__mark">DSLE</span>
                <p class="guest-card__name">DISKOVER Smart Learning Ecosystem</p>
            </div>

            @if (session('status'))
                <x-ui.alert type="success" class="u-mb-4">{{ session('status') }}</x-ui.alert>
            @endif

            @yield('content')
        </div>
        <p class="guest-footer">Academia DISKOVER US</p>
    </main>
</body>
</html>
