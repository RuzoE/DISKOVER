/**
 * Asistente de IA: desplaza el hilo al final, evita envíos dobles y permite
 * enviar con Enter (Shift+Enter para salto de línea). Mejora progresiva:
 * sin JS el formulario sigue funcionando con el botón.
 */
export function initAssistant(root = document) {
    const thread = root.querySelector('[data-ai-thread]');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }

    root.querySelectorAll('form[data-ai-form]').forEach((form) => {
        const input = form.querySelector('[data-ai-input]');
        const submit = form.querySelector('[data-ai-submit]');

        form.addEventListener('submit', () => {
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Pensando…';
            }
        });

        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                if (input.value.trim() !== '') {
                    form.requestSubmit();
                }
            }
        });
    });
}
