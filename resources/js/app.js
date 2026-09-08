import './bootstrap';
import { initConfirmForms } from './utils/confirm';
import { initContentForm } from './modules/teacher/content-form';

document.addEventListener('DOMContentLoaded', () => {
    initConfirmForms();
    initContentForm();
});
