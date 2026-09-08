@php
    $user = auth()->user();
    $initial = strtoupper(mb_substr($user?->name ?? '?', 0, 1));
@endphp

<header class="topbar">
    <button type="button" class="icon-button topbar__toggle" data-sidebar-toggle
            aria-label="Abrir menú" aria-controls="app-sidebar" aria-expanded="false">
        <x-ui.icon name="menu" :size="22" />
    </button>

    <div class="topbar__spacer"></div>

    <div class="user-menu" data-user-menu>
        <button type="button" class="user-menu__trigger" data-user-menu-trigger aria-expanded="false" aria-haspopup="true">
            <span class="avatar avatar--sm" aria-hidden="true">{{ $initial }}</span>
            <span class="user-menu__name">{{ $user?->name }}</span>
            <x-ui.icon name="chevron-down" :size="16" />
        </button>

        <div class="user-menu__panel" data-user-menu-panel hidden>
            <p class="user-menu__meta">
                <strong>{{ $user?->name }}</strong>
                <small>{{ $user?->email }}</small>
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="user-menu__item">
                    <x-ui.icon name="logout" :size="16" />
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</header>
