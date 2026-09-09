@php
    use App\Enums\RoleSlug;

    $user = auth()->user();
    $isTeacher = $user?->hasRole(RoleSlug::Teacher) || $user?->isAdmin();
    $isStudent = $user?->hasRole(RoleSlug::Student) || $user?->isAdmin();
    $initial = strtoupper(mb_substr($user?->name ?? '?', 0, 1));
@endphp

<aside class="sidebar" id="app-sidebar" aria-label="Navegación principal">
    <div class="sidebar__brand">
        <span class="sidebar__brand-mark">DSLE</span>
        <span class="sidebar__brand-text">
            <strong>DISKOVER</strong>
            <small>Smart Learning Ecosystem</small>
        </span>
    </div>

    <nav class="sidebar__nav">
        <div class="sidebar__group">
            <p class="sidebar__group-label">General</p>
            <a href="{{ route('dashboard') }}"
               class="sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <x-ui.icon name="home" />
                <span>Panel principal</span>
            </a>
            <a href="{{ route('assistant.index') }}"
               class="sidebar__link {{ request()->routeIs('assistant.*') ? 'is-active' : '' }}">
                <x-ui.icon name="sparkles" />
                <span>Asistente IA</span>
            </a>
        </div>

        @if ($user?->can('viewAny', App\Models\Course::class) || $isTeacher || $isStudent)
            <div class="sidebar__group">
                <p class="sidebar__group-label">Académico</p>

                @can('viewAny', App\Models\Course::class)
                    <a href="{{ route('coordinator.courses.index') }}"
                       class="sidebar__link {{ request()->routeIs('coordinator.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="book" />
                        <span>Cursos</span>
                    </a>
                @endcan

                @can('viewAny', App\Models\ImmersiveExperience::class)
                    <a href="{{ route('immersive.experiences.index') }}"
                       class="sidebar__link {{ request()->routeIs('immersive.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="cap" />
                        <span>Experiencias inmersivas</span>
                    </a>
                @endcan

                @if ($isTeacher)
                    <a href="{{ route('teacher.subjects.index') }}"
                       class="sidebar__link {{ request()->routeIs('teacher.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="stack" />
                        <span>Mis asignaturas</span>
                    </a>
                @endif

                @if ($isStudent)
                    <a href="{{ route('student.courses.index') }}"
                       class="sidebar__link {{ request()->routeIs('student.courses.*', 'student.subjects.*', 'student.activities.*', 'student.attempts.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="cap" />
                        <span>Mis cursos</span>
                    </a>
                    <a href="{{ route('student.profile.show') }}"
                       class="sidebar__link {{ request()->routeIs('student.profile.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="chart" />
                        <span>Mi progreso</span>
                    </a>
                    <a href="{{ route('analytics.student') }}"
                       class="sidebar__link {{ request()->routeIs('analytics.student') ? 'is-active' : '' }}">
                        <x-ui.icon name="chart" />
                        <span>Mi analítica</span>
                    </a>
                    <a href="{{ route('student.recommendations.index') }}"
                       class="sidebar__link {{ request()->routeIs('student.recommendations.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="clipboard" />
                        <span>Recomendaciones</span>
                    </a>
                    <a href="{{ route('student.immersive.index') }}"
                       class="sidebar__link {{ request()->routeIs('student.immersive.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="cap" />
                        <span>Experiencias VR/AR</span>
                    </a>
                    <a href="{{ route('student.transcript') }}"
                       class="sidebar__link {{ request()->routeIs('student.transcript') ? 'is-active' : '' }}">
                        <x-ui.icon name="clipboard" />
                        <span>Mi expediente</span>
                    </a>
                @endif

                @if ($user?->hasAnyRole(['admin', 'coordinator', 'teacher']))
                    <a href="{{ route('reports.index') }}"
                       class="sidebar__link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="clipboard" />
                        <span>Reportes</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($user?->can('viewAny', App\Models\User::class) || $user?->can('viewAny', App\Models\Role::class))
            <div class="sidebar__group">
                <p class="sidebar__group-label">Administración</p>

                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('admin.users.index') }}"
                       class="sidebar__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="users" />
                        <span>Usuarios</span>
                    </a>
                @endcan

                @can('viewAny', App\Models\Role::class)
                    <a href="{{ route('admin.roles.index') }}"
                       class="sidebar__link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="shield" />
                        <span>Roles</span>
                    </a>
                    <a href="{{ route('admin.permissions.index') }}"
                       class="sidebar__link {{ request()->routeIs('admin.permissions.*') ? 'is-active' : '' }}">
                        <x-ui.icon name="key" />
                        <span>Permisos</span>
                    </a>
                @endcan
            </div>
        @endif

        <div class="sidebar__group">
            <p class="sidebar__group-label">Próximamente</p>
            <span class="sidebar__link is-disabled">
                <x-ui.icon name="shield" />
                <span>Auditoría avanzada</span>
                <em class="sidebar__badge">Fase 11</em>
            </span>
            <span class="sidebar__link is-disabled">
                <x-ui.icon name="stack" />
                <span>DevOps y despliegue</span>
                <em class="sidebar__badge">Fase 13</em>
            </span>
        </div>
    </nav>

    <div class="sidebar__user">
        <span class="avatar" aria-hidden="true">{{ $initial }}</span>
        <span class="sidebar__user-info">
            <strong>{{ $user?->name }}</strong>
            <small>{{ $user?->email }}</small>
        </span>
        <form method="POST" action="{{ route('logout') }}" class="sidebar__logout">
            @csrf
            <button type="submit" class="icon-button" title="Cerrar sesión" aria-label="Cerrar sesión">
                <x-ui.icon name="logout" :size="18" />
            </button>
        </form>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>
