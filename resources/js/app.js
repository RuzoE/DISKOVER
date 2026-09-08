import './bootstrap';
import { initConfirmForms } from './utils/confirm';
import { initContentForm } from './modules/teacher/content-form';
import { initShell } from './modules/layout/shell';

document.addEventListener('DOMContentLoaded', () => {
    initShell();
    initConfirmForms();
    initContentForm();
});
