@props([
    'action',
    'method' => 'POST',
    'role' => null,
    'permissionGroups' => [],
])

@php
    $selected = old('permissions', $role?->permissions->pluck('slug')->all() ?? []);
    $isSystem = (bool) ($role?->is_system);
@endphp

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form__grid">
        <x-ui.input name="name" label="Nombre" :value="$role?->name" required autofocus />
        <x-ui.input
            name="slug"
            label="Identificador"
            :value="$role?->slug"
            :readonly="$isSystem"
            hint="{{ $isSystem ? 'Rol del sistema: el identificador no puede cambiarse.' : 'Sólo letras, números, guion y guion bajo.' }}"
            required
        />
    </div>

    <x-ui.input name="description" label="Descripción" :value="$role?->description" />

    <fieldset class="fieldset">
        <legend class="fieldset__legend">Permisos</legend>

        @forelse ($permissionGroups as $group => $permissions)
            <div class="permission-group">
                <p class="permission-group__title">{{ ucfirst($group) }}</p>
                <div class="checkbox-grid">
                    @foreach ($permissions as $permission)
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->slug }}"
                                @checked(in_array($permission->slug, $selected, true))
                            >
                            <span>{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted">No hay permisos en el catálogo.</p>
        @endforelse

        @error('permissions.*')<p class="field__error">{{ $message }}</p>@enderror
    </fieldset>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $role ? 'Guardar cambios' : 'Crear rol' }}
        </x-ui.button>
        <x-ui.button :href="route('admin.roles.index')" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
