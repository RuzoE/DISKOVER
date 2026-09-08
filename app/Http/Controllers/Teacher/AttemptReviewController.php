<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ReviewAttemptRequest;
use App\Models\Attempt;
use App\Services\Academic\AttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Revisión de las respuestas abiertas de un intento por parte del docente.
 */
class AttemptReviewController extends Controller
{
    public function __construct(private readonly AttemptService $attempts) {}

    public function edit(Attempt $attempt): View
    {
        $this->authorize('grade', $attempt);

        $attempt->load([
            'evaluation.activity.subject.course',
            'student',
            'answers.question.options',
        ]);

        return view('teacher.attempts.review', ['attempt' => $attempt]);
    }

    public function update(ReviewAttemptRequest $request, Attempt $attempt): RedirectResponse
    {
        $scores = collect($request->input('scores', []))
            ->mapWithKeys(fn ($value, $answerId) => [(int) $answerId => (float) $value])
            ->all();

        $this->attempts->gradeOpenAnswers($attempt, $scores);

        return redirect()
            ->route('teacher.activities.gradebook', $attempt->evaluation->activity_id)
            ->with('status', 'Intento revisado y calificado.');
    }
}
