<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SaveAttemptRequest;
use App\Models\Activity;
use App\Models\Attempt;
use App\Services\Academic\AttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttemptController extends Controller
{
    public function __construct(private readonly AttemptService $attempts) {}

    /**
     * Comienza (o retoma) un intento de la evaluación de la actividad.
     */
    public function store(Activity $activity): RedirectResponse
    {
        abort_unless($activity->isQuiz(), 404);
        $evaluation = $activity->evaluation()->firstOrFail();

        $this->authorize('create', [Attempt::class, $evaluation]);

        $attempt = $this->attempts->startOrResume($evaluation, request()->user());

        return redirect()->route('student.attempts.show', $attempt);
    }

    public function show(Attempt $attempt): View
    {
        $this->authorize('view', $attempt);

        // Si el tiempo se agotó mientras estaba en curso, se envía con lo guardado.
        if ($attempt->status->isOpen() && $attempt->deadlineReached()) {
            $this->attempts->submit($attempt, []);
            $attempt->refresh();
        }

        $attempt->load([
            'evaluation.activity.subject',
            'evaluation.questions.options',
            'answers',
        ]);

        $view = $attempt->status->isOpen() ? 'student.attempts.take' : 'student.attempts.result';

        return view($view, ['attempt' => $attempt]);
    }

    public function update(SaveAttemptRequest $request, Attempt $attempt): RedirectResponse
    {
        $this->attempts->saveAnswers($attempt, $request->answers());

        return redirect()
            ->route('student.attempts.show', $attempt)
            ->with('status', 'Respuestas guardadas.');
    }

    public function submit(SaveAttemptRequest $request, Attempt $attempt): RedirectResponse
    {
        $this->attempts->submit($attempt, $request->answers());

        return redirect()
            ->route('student.attempts.show', $attempt)
            ->with('status', 'Intento enviado.');
    }
}
