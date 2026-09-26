{{--
    Asistente DSLE flotante: botón fijo + panel lateral de chat que usa
    POST /assistant/quick (JSON) sin recargar la página. Sólo se muestra a
    estudiantes y administradores; la ruta aplica la misma restricción.
--}}
@php
    $user = auth()->user();
    $visible = $user?->hasAnyRole([App\Enums\RoleSlug::Student, App\Enums\RoleSlug::Admin]);
    $suggestions = [
        ['label' => '¿Qué trabajos me faltan?', 'send' => true],
        ['label' => '¿Cómo voy en mis asignaturas?', 'send' => true],
        ['label' => 'Explícame un tema', 'send' => false, 'prefill' => 'Explícame el tema: '],
    ];
@endphp

@if ($visible)
    <div class="quick-assistant" data-quick-assistant data-endpoint="{{ route('assistant.quick') }}">
        <button type="button" class="quick-assistant__toggle" data-qa-toggle
                aria-controls="quick-assistant-panel" aria-expanded="false" aria-label="Abrir el Asistente DSLE">
            <x-ui.icon name="sparkles" :size="20" />
            <span class="quick-assistant__toggle-label">Asistente DSLE</span>
        </button>

        <section id="quick-assistant-panel" class="quick-assistant__panel" data-qa-panel hidden
                 role="dialog" aria-modal="false" aria-labelledby="quick-assistant-title">
            <header class="quick-assistant__header">
                <h2 id="quick-assistant-title" class="quick-assistant__title">Asistente DSLE</h2>
                <a href="{{ route('assistant.index') }}" class="link quick-assistant__history">Historial</a>
                <button type="button" class="icon-button" data-qa-close aria-label="Cerrar el asistente">
                    <span aria-hidden="true">&times;</span>
                </button>
            </header>

            <div class="quick-assistant__thread chat-thread" data-qa-thread aria-live="polite" aria-relevant="additions">
                <div class="chat-msg chat-msg--assistant">
                    <span class="chat-msg__role">Asistente</span>
                    <div class="chat-msg__body">
                        ¡Hola{{ $user->hasRole(App\Enums\RoleSlug::Student) ? ', '.App\Services\AI\AcademicContextBuilder::firstName($user) : '' }}!
                        Te ayudo a organizar tus trabajos, entender temas y revisar tu progreso. No resuelvo actividades evaluables,
                        pero sí te guío para que las hagas tú.
                    </div>
                </div>
            </div>

            <p class="quick-assistant__typing" data-qa-typing hidden>
                <span class="quick-assistant__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                El asistente está escribiendo…
            </p>

            <div class="quick-assistant__suggestions" data-qa-suggestions>
                @foreach ($suggestions as $suggestion)
                    <button type="button" class="btn btn--secondary btn--sm" data-qa-suggestion
                            data-send="{{ $suggestion['send'] ? '1' : '0' }}"
                            data-text="{{ $suggestion['prefill'] ?? $suggestion['label'] }}">{{ $suggestion['label'] }}</button>
                @endforeach
            </div>

            <form class="quick-assistant__composer" data-qa-form>
                <label for="quick-assistant-input" class="u-sr-only">Escribe tu pregunta</label>
                <textarea id="quick-assistant-input" name="message" rows="2" class="field__control" required
                          maxlength="2000" data-qa-input placeholder="Escribe tu pregunta…"></textarea>
                <button type="submit" class="btn btn--primary" data-qa-submit>Enviar</button>
            </form>
        </section>
    </div>
@endif
