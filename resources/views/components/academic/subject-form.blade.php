@props([
    'action',
    'method' => 'POST',
    'course',
    'subject' => null,
    'teachers' => [],
    'statuses' => [],
])

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form__grid">
        <x-ui.input name="code" label="Código" :value="$subject?->code" required autofocus
                    hint="Único en toda la plataforma (ej. DSLE-101-A)." />
        <x-ui.select name="status" label="Estado" :options="$statuses"
                     :selected="$subject?->status?->value ?? 'draft'" required />
    </div>

    <x-ui.input name="name" label="Nombre" :value="$subject?->name" required />

    <x-ui.textarea name="description" label="Descripción" :value="$subject?->description" rows="3" />

    <div class="form__grid">
        <x-ui.select name="teacher_id" label="Docente asignado" :options="$teachers"
                     :selected="$subject?->teacher_id" placeholder="— Sin asignar —" />
        <x-ui.input type="number" name="position" label="Orden" min="0"
                    :value="$subject?->position ?? 0"
                    hint="Determina el orden de aparición dentro del curso." />
    </div>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $subject ? 'Guardar cambios' : 'Crear asignatura' }}
        </x-ui.button>
        <x-ui.button :href="route('coordinator.courses.subjects.index', $course)" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
