<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\QuestionRequest;
use App\Models\Activity;
use App\Models\Question;
use App\Services\Academic\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questions) {}

    public function create(Activity $activity): View
    {
        $this->authorize('update', $activity);
        abort_unless($activity->isQuiz(), 404);

        return view('teacher.questions.create', [
            'activity' => $activity->load('subject.course'),
            'types' => QuestionType::options(),
        ]);
    }

    public function store(QuestionRequest $request, Activity $activity): RedirectResponse
    {
        $evaluation = $activity->evaluation()->firstOrCreate([]);
        $this->questions->create($evaluation, $request->validated() + [
            'options' => $request->input('options', []),
            'correct' => $request->input('correct', []),
            'boolean_answer' => $request->input('boolean_answer'),
        ]);

        return redirect()
            ->route('teacher.activities.evaluation.edit', $activity)
            ->with('status', 'Pregunta añadida.');
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question->evaluation->activity);

        return view('teacher.questions.edit', [
            'question' => $question->load('options', 'evaluation.activity.subject.course'),
            'types' => QuestionType::options(),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questions->update($question, $request->validated() + [
            'options' => $request->input('options', []),
            'correct' => $request->input('correct', []),
            'boolean_answer' => $request->input('boolean_answer'),
        ]);

        return redirect()
            ->route('teacher.activities.evaluation.edit', $question->evaluation->activity_id)
            ->with('status', 'Pregunta actualizada.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('update', $question->evaluation->activity);

        $activityId = $question->evaluation->activity_id;
        $this->questions->delete($question);

        return redirect()
            ->route('teacher.activities.evaluation.edit', $activityId)
            ->with('status', 'Pregunta eliminada.');
    }
}
