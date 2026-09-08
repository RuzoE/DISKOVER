@props([
    'action',
    'method' => 'POST',
    'activity',
    'question' => null,
    'types' => [],
])

@php
    $type = old('type', $question?->type?->value ?? 'single');
    $opts = old('options', $question
        ? $question->options->map(fn ($o) => ['text' => $o->text])->all()
        : [['text' => ''], ['text' => ''], ['text' => '']]);
    $correct = old('correct', $question ? $question->options->filter->is_correct->keys()->all() : []);
    $boolAnswer = old('boolean_answer', $question && $question->type->value === 'boolean'
        ? ($question->options->firstWhere('is_correct', true)?->position === 1 ? 'true' : 'false')
        : 'true');
@endphp

<form method="POST" action="{{ $action }}" class="form form--stacked" data-question-form>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-ui.select name="type" label="Tipo de pregunta" :options="$types" :selected="$type" required data-question-type />

    <x-ui.textarea name="statement" label="Enunciado" :value="$question?->statement" rows="3" required autofocus />

    <x-ui.input type="number" name="score" label="Puntuación" step="0.25" min="0.25"
                :value="$question?->score ?? 1" required />

    {{-- Opciones (single / multiple) --}}
    <div data-question-block="options" class="fieldset">
        <p class="fieldset__legend">Opciones</p>
        <p class="field__hint">Marca la(s) opción(es) correcta(s).</p>

        <div data-options-list>
            @foreach ($opts as $i => $opt)
                <div class="option-row" data-option-row>
                    <input type="checkbox" name="correct[]" value="{{ $i }}"
                           @checked(in_array((string) $i, array_map('strval', (array) $correct), true))
                           aria-label="Correcta">
                    <input type="text" name="options[{{ $i }}][text]" class="field__control"
                           value="{{ $opt['text'] ?? '' }}" placeholder="Texto de la opción">
                    <button type="button" class="link link--danger" data-remove-option>Quitar</button>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn--secondary btn--sm" data-add-option>Añadir opción</button>
        @error('options')<p class="field__error">{{ $message }}</p>@enderror
        @error('correct')<p class="field__error">{{ $message }}</p>@enderror
    </div>

    {{-- Verdadero / Falso --}}
    <div data-question-block="boolean" class="fieldset" hidden>
        <p class="fieldset__legend">Respuesta correcta</p>
        <label class="checkbox"><input type="radio" name="boolean_answer" value="true" @checked($boolAnswer === 'true')> <span>Verdadero</span></label>
        <label class="checkbox"><input type="radio" name="boolean_answer" value="false" @checked($boolAnswer === 'false')> <span>Falso</span></label>
        @error('boolean_answer')<p class="field__error">{{ $message }}</p>@enderror
    </div>

    {{-- Abierta --}}
    <div data-question-block="open" class="alert alert--info" hidden>
        La respuesta abierta la califica el docente al revisar cada intento.
    </div>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $question ? 'Guardar pregunta' : 'Añadir pregunta' }}
        </x-ui.button>
        <x-ui.button :href="route('teacher.activities.evaluation.edit', $activity)" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
