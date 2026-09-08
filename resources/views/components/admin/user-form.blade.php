@props([
    'action',
    'method' => 'POST',
    'user' => null,
    'roles' => [],
    'statuses' => [],
])

@php
    $selectedRoles = old('roles', $user?->roles->pluck('slug')->all() ?? []);
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

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $user ? 'Guardar cambios' : 'Crear usuario' }}
        </x-ui.button>
        <x-ui.button :href="route('admin.users.index')" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
