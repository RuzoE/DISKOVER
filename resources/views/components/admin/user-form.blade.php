@props([
    'action',
    'method' => 'POST',
    'user' => null,
    'roles' => [],
    'statuses' => [],
    'permissionGroups' => [],
])

@php
    $selectedRoles = old('roles', $user?->roles->pluck('slug')->all() ?? []);
    $selectedPerms = array_map('strval', old('direct_permissions', $user?->directPermissions->pluck('slug')->all() ?? []));
@endphp

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-ui.input
        name="name"
        label="Nombre completo"
        :value="$user?->name"
        required
        autofocus
    />

    <x-ui.input
        name="email"
        type="email"
        label="Correo electrónico"
        :value="$user?->email"
        autocomplete="off"
        required
    />

    <div class="form__grid">
        <x-ui.input
            name="password"
            type="password"
            label="{{ $user ? 'Nueva contraseña (opcional)' : 'Contraseña' }}"
            autocomplete="new-password"
            :required="! $user"
            :hint="$user ? 'Déjala en blanco para no cambiarla.' : null"
        />

        <x-ui.input
            name="password_confirmation"
            type="password"
            label="Confirmar contraseña"
            autocomplete="new-password"
            :required="! $user"
        />
    </div>

    <x-ui.select
        name="status"
        label="Estado"
        :options="$statuses"
        :selected="$user?->status?->value ?? 'active'"
        required
    />

    <fieldset class="fieldset">
        <legend class="fieldset__legend">Roles</legend>
        <div class="checkbox-grid">
            @foreach ($roles as $role)
                <label class="checkbox">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->slug }}"
                        @checked(in_array($role->slug, $selectedRoles, true))
                    >
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
        @error('roles')<p class="field__error">{{ $message }}</p>@enderror
        @error('roles.*')<p class="field__error">{{ $message }}</p>@enderror
    </fieldset>

    @if (count($permissionGroups) > 0)
        <fieldset class="fieldset">
            <legend class="fieldset__legend">Permisos directos</legend>
            <p class="text-muted u-mb-3">
                Permisos adicionales concedidos a esta persona, independientes de sus roles.
            </p>
            @foreach ($permissionGroups as $group => $permissions)
                <div class="permission-group">
                    <p class="permission-group__title">{{ ucfirst($group) }}</p>
                    <div class="checkbox-grid">
                        @foreach ($permissions as $permission)
                            <label class="checkbox">
                                <input
                                    type="checkbox"
                                    name="direct_permissions[]"
                                    value="{{ $permission->slug }}"
                                    @checked(in_array($permission->slug, $selectedPerms, true))
                                >
                                <span>{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @error('direct_permissions')<p class="field__error">{{ $message }}</p>@enderror
            @error('direct_permissions.*')<p class="field__error">{{ $message }}</p>@enderror
        </fieldset>
    @endif

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $user ? 'Guardar cambios' : 'Crear usuario' }}
        </x-ui.button>
        <x-ui.button :href="route('admin.users.index')" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
