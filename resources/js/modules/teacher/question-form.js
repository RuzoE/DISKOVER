/**
 * Formulario de preguntas: muestra el bloque adecuado (opciones / verdadero-falso
 * / abierta) según el tipo, y permite añadir o quitar filas de opción.
 */
export function initQuestionForm(root = document) {
    root.querySelectorAll('form[data-question-form]').forEach((form) => {
        const typeSelect = form.querySelector('[data-question-type]');
        if (!typeSelect) {
            return;
        }

        const blocks = {
            options: form.querySelector('[data-question-block="options"]'),
            boolean: form.querySelector('[data-question-block="boolean"]'),
            open: form.querySelector('[data-question-block="open"]'),
        };

        const sync = () => {
            const type = typeSelect.value;
            blocks.options.hidden = !(type === 'single' || type === 'multiple');
            blocks.boolean.hidden = type !== 'boolean';
            blocks.open.hidden = type !== 'open';

            // radio para "single", checkbox para "multiple"
            form.querySelectorAll('[data-option-row] input[name="correct[]"]').forEach((input) => {
                input.type = type === 'multiple' ? 'checkbox' : 'radio';
            });
        };

        typeSelect.addEventListener('change', sync);
        sync();

        const list = form.querySelector('[data-options-list]');
        form.querySelector('[data-add-option]')?.addEventListener('click', () => {
            const index = list.querySelectorAll('[data-option-row]').length;
            const row = document.createElement('div');
            row.className = 'option-row';
            row.dataset.optionRow = '';
            row.innerHTML = `
                <input type="${typeSelect.value === 'multiple' ? 'checkbox' : 'radio'}" name="correct[]" value="${index}" aria-label="Correcta">
                <input type="text" name="options[${index}][text]" class="field__control" placeholder="Texto de la opción">
                <button type="button" class="link link--danger" data-remove-option>Quitar</button>`;
            list.appendChild(row);
        });

        list?.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove-option]')) {
                event.target.closest('[data-option-row]')?.remove();
            }
        });
    });
}
