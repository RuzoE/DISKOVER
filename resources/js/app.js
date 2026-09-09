import './bootstrap';
import { initConfirmForms } from './utils/confirm';
import { initContentForm } from './modules/teacher/content-form';
import { initQuestionForm } from './modules/teacher/question-form';
import { initQuizTimer } from './modules/student/quiz';
import { initAssistant } from './modules/ai/assistant';
import { initImmersiveSimulator } from './modules/immersive/simulator';
import { initShell } from './modules/layout/shell';

document.addEventListener('DOMContentLoaded', () => {
    initShell();
    initConfirmForms();
    initContentForm();
    initQuestionForm();
    initQuizTimer();
    initAssistant();
    initImmersiveSimulator();
});
