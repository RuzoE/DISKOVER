/**
 * Simulador de cliente inmersivo: sin build de Unity, envía un resultado de
 * prueba a la API `POST /api/v1/immersive/sessions/{token}/complete`. Es la
 * misma llamada que haría el cliente real.
 */
export function initImmersiveSimulator(root = document) {
    const box = root.querySelector('[data-immersive-sim]');
    if (!box) {
        return;
    }

    const url = box.dataset.completeUrl;
    const scoreInput = box.querySelector('[data-sim-score]');
    const button = box.querySelector('[data-sim-submit]');
    const error = box.querySelector('[data-sim-error]');

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Enviando…';
        error.hidden = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    score: Number(scoreInput.value || 0),
                    payload: { source: 'simulator', at: new Date().toISOString() },
                }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || `Error ${response.status}`);
            }

            window.location.reload();
        } catch (e) {
            error.textContent = e.message || 'No se pudo registrar el resultado.';
            error.hidden = false;
            button.disabled = false;
            button.textContent = 'Finalizar experiencia';
        }
    });
}
