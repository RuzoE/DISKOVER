@props([
    'action',
    'method' => 'POST',
    'subject',
    'content' => null,
    'types' => [],
])

<form method="POST" action="{{ $action }}" class="form form--stacked" data-content-form>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-ui.input name="title" label="Título" :value="$content?->title" required autofocus />

    <div class="form__grid">
        <x-ui.select name="type" label="Tipo" :options="$types"
                     :selected="$content?->type?->value ?? 'text'" required data-content-type />
        <x-ui.input type="number" name="position" label="Orden" min="0"
                    :value="$content?->position ?? 0" />
    </div>

    <div data-content-field="url">
        <x-ui.input type="url" name="url" label="Enlace (URL)" :value="$content?->url"
                    hint="Obligatorio para documento, vídeo o enlace." />
    </div>

    <div data-content-field="body">
        <x-ui.textarea name="body" label="Contenido" :value="$content?->body" rows="8"
                       hint="Obligatorio para el tipo Texto." />
    </div>

    <label class="checkbox">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1"
               @checked(old('is_published', $content?->is_published)) >
        <span>Publicado (visible para estudiantes)</span>
    </label>

    <div class="form__actions">
        <x-ui.button type="submit" variant="primary">
            {{ $content ? 'Guardar cambios' : 'Añadir contenido' }}
        </x-ui.button>
        <x-ui.button :href="route('teacher.subjects.contents.index', $subject)" variant="ghost">Cancelar</x-ui.button>
    </div>
</form>
