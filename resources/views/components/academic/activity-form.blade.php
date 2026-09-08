@props([
    'action',
    'method' => 'POST',
    'subject',
    'activity' => null,
    'types' => [],
])

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    @if ($activity)
        <p class="text-muted">Tipo: <strong>{{ $activity->type->label() }}</strong> (no se puede cambiar)</p>
    @else
        <x-ui.select name="type" label="Tipo de actividad" :options="$types" :selected="old('type', 'task')" required
                     hint="«Trabajo»: entrega calificada a mano. «Evaluación»: cuestionario en línea." />
    @endif

    <x-ui.input name="title" label="Título" :value="$activity?->title" required autofocus />
    <x-ui.textarea name="description" label="Descripción" :value="$activity?->description" rows="3" />
    <x-ui.textarea name="instructions" label="Instrucciones" :value="$activity?->instructions" rows="3" />

    <div class="form__grid">
        <x-ui.input type="number" name="max_score" label="Puntuación máxima" step="0.01" min="1"
                    :value="$activity?->max_score ?? 100" required />
        <div><!-- spacer --></div>
    </div>

    <div class="form__grid">
        <x-ui.input type="datetime-local" name="opens_at" label="Apertura (opcional)"
                    :value="old('opens_at', $activity?->opens_at?->format('Y-m-d\TH:i'))" />
        <x-ui.input type="datetime-local" name="due_at" label="Entrega (opcional)"
                    :value="old('due_at', $activity?->due_at?->format('Y-m-d\TH:i'))" />
    </div>

    <label class="checkbox">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $activity?->is_published))>
        <span>Publicada (visible para estudiantes)</span>
    </label>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $activity ? 'Guardar cambios' : 'Crear actividad' }}
        </x-ui.button>
        <x-ui.button :href="route('teacher.subjects.activities.index', $subject)" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
