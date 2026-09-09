@props([
    'action',
    'method' => 'POST',
    'experience' => null,
    'providers' => [],
    'statuses' => [],
    'subjects' => [],
    'activityOptions' => [],
])

@php
    $linkedSubjects = old('subject_ids', $experience?->subjects->pluck('id')->all() ?? []);
    $requiredSubjects = old('required_subject_ids', $experience
        ? $experience->subjects->filter(fn ($s) => $s->pivot->is_required)->pluck('id')->all()
        : []);
    $configJson = old('config', $experience && $experience->config
        ? json_encode($experience->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '');
@endphp

<form method="POST" action="{{ $action }}" class="form form--stacked">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-ui.input name="title" label="Título" :value="$experience?->title" required autofocus />
    <x-ui.input name="slug" label="Identificador (opcional)" :value="$experience?->slug"
                hint="Se genera automáticamente si lo dejas vacío." />
    <x-ui.textarea name="description" label="Descripción" :value="$experience?->description" rows="3" />

    <div class="form__grid">
        <x-ui.select name="provider" label="Proveedor" :options="$providers"
                     :selected="$experience?->provider?->value ?? 'simulator'" required />
        <x-ui.select name="status" label="Estado" :options="$statuses"
                     :selected="$experience?->status?->value ?? 'draft'" required />
    </div>

    <x-ui.input type="url" name="launch_url" label="URL de lanzamiento" :value="$experience?->launch_url"
                hint="Obligatoria salvo para el simulador. Es donde vive la build de Unity (WebGL) o el enlace." />

    <div class="form__grid">
        <x-ui.input type="number" name="max_score" label="Puntuación máxima" step="0.01" min="1"
                    :value="$experience?->max_score ?? 100" required />
        <x-ui.select name="activity_id" label="Actividad vinculada (opcional)"
                     :options="$activityOptions"
                     :selected="$experience?->activity_id" placeholder="— Sin vincular —"
                     hint="Si se vincula, el resultado de la experiencia calificará esa actividad." />
    </div>

    <x-ui.textarea name="config" label="Configuración (JSON, opcional)" :value="$configJson" rows="4"
                   hint='Se envía tal cual al cliente. Ej.: {"scene":"lab-01","difficulty":"normal"}' />

    <fieldset class="fieldset">
        <legend class="fieldset__legend">Asignaturas en las que aparece</legend>
        <div class="checkbox-grid">
            @foreach ($subjects as $subject)
                <label class="checkbox">
                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                           @checked(in_array($subject->id, array_map('intval', $linkedSubjects), true))>
                    <span>{{ $subject->name }} <span class="text-muted">({{ $subject->course->name }})</span></span>
                </label>
            @endforeach
        </div>
        @error('subject_ids')<p class="field__error">{{ $message }}</p>@enderror
    </fieldset>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $experience ? 'Guardar cambios' : 'Registrar experiencia' }}
        </x-ui.button>
        <x-ui.button :href="route('immersive.experiences.index')" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
