<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell">
        <x-navigation.sidebar />

        <div class="app-content">
            <x-navigation.navbar />

            <main class="app-main">
                <div class="app-container">
                    @if (session('status'))
                        <x-ui.alert type="success" class="u-mb-4">{{ session('status') }}</x-ui.alert>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
