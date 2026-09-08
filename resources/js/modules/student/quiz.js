/**
 * Cuenta atrás de un intento con tiempo límite. Al agotarse, envía el
 * formulario automáticamente (el servidor también valida el plazo).
 */
export function initQuizTimer(root = document) {
    const el = root.querySelector('[data-quiz-deadline]');
    const form = root.querySelector('form[data-quiz-form]');
    if (!el || !form) {
        return;
    }

    const deadline = new Date(el.dataset.quizDeadline).getTime();
    const out = el.querySelector('[data-quiz-remaining]');
    let submitted = false;

    const tick = () => {
        const remaining = deadline - Date.now();

        if (remaining <= 0) {
            out.textContent = '00:00';
            if (!submitted) {
                submitted = true;
                form.submit();
            }
            return;
        }

        const total = Math.floor(remaining / 1000);
        const m = String(Math.floor(total / 60)).padStart(2, '0');
        const s = String(total % 60).padStart(2, '0');
        out.textContent = `${m}:${s}`;
        el.classList.toggle('is-urgent', remaining < 60000);

        window.setTimeout(tick, 1000);
    };

    tick();
}
