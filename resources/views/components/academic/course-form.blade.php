@props([
    'action',
    'method' => 'POST',
    'course' => null,
    'statuses' => [],
])

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form__grid">
        <x-ui.input name="code" label="Código" :value="$course?->code" required autofocus
                    hint="Identificador corto y único (ej. DSLE-101)." />
        <x-ui.select name="status" label="Estado" :options="$statuses"
                     :selected="$course?->status?->value ?? 'draft'" required />
    </div>

    <x-ui.input name="name" label="Nombre" :value="$course?->name" required />

    <x-ui.textarea name="description" label="Descripción" :value="$course?->description" rows="4" />

    <div class="form__grid">
        <x-ui.input type="date" name="starts_on" label="Fecha de inicio"
                    :value="$course?->starts_on?->format('Y-m-d')" />
        <x-ui.input type="date" name="ends_on" label="Fecha de fin"
                    :value="$course?->ends_on?->format('Y-m-d')" />
    </div>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $course ? 'Guardar cambios' : 'Crear curso' }}
        </x-ui.button>
        <x-ui.button :href="route('coordinator.courses.index')" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
