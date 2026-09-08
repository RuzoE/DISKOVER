/**
 * Muestra u oculta los campos "enlace" y "contenido" del formulario de
 * contenidos según el tipo seleccionado. Mejora progresiva: sin JS ambos
 * campos quedan visibles y el servidor valida igualmente.
 */
export function initContentForm(root = document) {
    root.querySelectorAll('form[data-content-form]').forEach((form) => {
        const typeSelect = form.querySelector('[data-content-type]');
        if (!typeSelect) {
            return;
        }

        const urlField = form.querySelector('[data-content-field="url"]');
        const bodyField = form.querySelector('[data-content-field="body"]');

        const sync = () => {
            const isText = typeSelect.value === 'text';
            if (urlField) urlField.hidden = isText;
            if (bodyField) bodyField.hidden = !isText;
        };

        typeSelect.addEventListener('change', sync);
        sync();
    });
}
