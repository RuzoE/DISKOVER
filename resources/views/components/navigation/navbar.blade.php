@php
    use App\Enums\RoleSlug;

    $user = auth()->user();
    $isTeacher = $user?->hasRole(RoleSlug::Teacher) || $user?->isAdmin();
    $isStudent = $user?->hasRole(RoleSlug::Student) || $user?->isAdmin();
@endphp

<header class="navbar">
    <div class="navbar__inner">
        <a href="{{ route('dashboard') }}" class="navbar__brand">
            <span class="navbar__brand-mark">DSLE</span>
            <span class="navbar__brand-text">DISKOVER Smart Learning Ecosystem</span>
        </a>

        <nav class="navbar__nav" aria-label="Navegación principal">
            <a href="{{ route('dashboard') }}"
               class="navbar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Panel</a>

            @can('viewAny', App\Models\Course::class)
                <a href="{{ route('coordinator.courses.index') }}"
                   class="navbar__link {{ request()->routeIs('coordinator.*') ? 'is-active' : '' }}">Cursos</a>
            @endcan

            @if ($isTeacher)
                <a href="{{ route('teacher.subjects.index') }}"
                   class="navbar__link {{ request()->routeIs('teacher.*') ? 'is-active' : '' }}">Mis asignaturas</a>
            @endif

            @if ($isStudent)
                <a href="{{ route('student.courses.index') }}"
                   class="navbar__link {{ request()->routeIs('student.*') ? 'is-active' : '' }}">Mis cursos</a>
            @endif

            @can('viewAny', App\Models\User::class)
                <a href="{{ route('admin.users.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">Usuarios</a>
            @endcan

            @can('viewAny', App\Models\Role::class)
                <a href="{{ route('admin.roles.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}">Roles</a>
                <a href="{{ route('admin.permissions.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.permissions.*') ? 'is-active' : '' }}">Permisos</a>
            @endcan
        </nav>

        <div class="navbar__user">
            <span class="navbar__user-name">{{ $user?->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
</header>
