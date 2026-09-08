import './bootstrap';
import { initConfirmForms } from './utils/confirm';
import { initContentForm } from './modules/teacher/content-form';
import { initQuestionForm } from './modules/teacher/question-form';
import { initQuizTimer } from './modules/student/quiz';
import { initShell } from './modules/layout/shell';

document.addEventListener('DOMContentLoaded', () => {
    initShell();
    initConfirmForms();
    initContentForm();
    initQuestionForm();
    initQuizTimer();
});
