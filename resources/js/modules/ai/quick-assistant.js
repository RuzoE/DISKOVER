/**
 * Asistente DSLE flotante: abre/cierra el panel lateral, envía los mensajes a
 * POST /assistant/quick con fetch (CSRF por cabecera) y pinta la respuesta
 * que devuelve el servidor ya escapada (componente x-ai.bubble).
 */
const ERRORS = {
    419: 'Tu sesión ha caducado. Recarga la página para seguir.',
    422: 'Escribe un mensaje de hasta 2000 caracteres.',
    429: 'Has enviado muchos mensajes seguidos; espera un minuto e inténtalo de nuevo.',
    default: 'No se pudo contactar con el asistente. Revisa tu conexión e inténtalo de nuevo.',
};

export function initQuickAssistant(root = document) {
    const widget = root.querySelector('[data-quick-assistant]');
    if (!widget) {
        return;
    }

    const toggle = widget.querySelector('[data-qa-toggle]');
    const panel = widget.querySelector('[data-qa-panel]');
    const closeButton = widget.querySelector('[data-qa-close]');
    const thread = widget.querySelector('[data-qa-thread]');
    const typing = widget.querySelector('[data-qa-typing]');
    const form = widget.querySelector('[data-qa-form]');
    const input = widget.querySelector('[data-qa-input]');
    const submit = widget.querySelector('[data-qa-submit]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let busy = false;

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));
        widget.classList.toggle('is-open', isOpen);
        if (isOpen) {
            input.focus();
            scrollToEnd();
        } else {
            toggle.focus();
        }
    };

    const scrollToEnd = () => {
        thread.scrollTop = thread.scrollHeight;
    };

    const appendBubble = (html) => {
        thread.insertAdjacentHTML('beforeend', html);
        scrollToEnd();
    };

    const appendText = (text, variant) => {
        const bubble = document.createElement('div');
        bubble.className = `chat-msg chat-msg--${variant}`;

        const role = document.createElement('span');
        role.className = 'chat-msg__role';
        role.textContent = variant === 'user' ? 'Tú' : 'Asistente';

        const body = document.createElement('div');
        body.className = 'chat-msg__body';
        body.textContent = text;

        bubble.append(role, body);
        thread.append(bubble);
        scrollToEnd();
    };

    const setBusy = (isBusy) => {
        busy = isBusy;
        typing.hidden = !isBusy;
        submit.disabled = isBusy;
        widget.querySelectorAll('[data-qa-suggestion]').forEach((b) => { b.disabled = isBusy; });
    };

    const send = async (message) => {
        const text = message.trim();
        if (text === '' || busy) {
            return;
        }

        appendText(text, 'user');
        input.value = '';
        setBusy(true);

        try {
            const response = await fetch(widget.dataset.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: text }),
            });

            if (!response.ok) {
                appendText(ERRORS[response.status] ?? ERRORS.default, 'assistant chat-msg--failed');
                return;
            }

            const data = await response.json();
            appendBubble(data.html);
        } catch {
            appendText(ERRORS.default, 'assistant chat-msg--failed');
        } finally {
            setBusy(false);
            input.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));

    widget.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            event.stopPropagation();
            setOpen(false);
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        send(input.value);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            send(input.value);
        }
    });

    widget.querySelectorAll('[data-qa-suggestion]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.send === '1') {
                send(button.dataset.text);
                return;
            }
            input.value = button.dataset.text;
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        });
    });
}
