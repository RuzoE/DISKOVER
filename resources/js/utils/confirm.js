/**
 * Intercepta el envío de cualquier formulario con [data-confirm] y pide
 * confirmación al usuario antes de continuar (usado en acciones destructivas
 * como eliminar usuarios o roles).
 */
export function initConfirmForms(root = document) {
    root.querySelectorAll('form[data-confirm]').forEach((form) => {
        if (form.dataset.confirmBound === 'true') {
            return;
        }

        form.dataset.confirmBound = 'true';
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}
