<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\UpdateEvaluationRequest;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);
        abort_unless($activity->isQuiz(), 404);

        return view('teacher.evaluations.edit', [
            'activity' => $activity->load('subject.course'),
            'evaluation' => $activity->evaluation()->firstOrCreate([]),
        ]);
    }

    public function update(UpdateEvaluationRequest $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->isQuiz(), 404);

        $activity->evaluation()->firstOrCreate([])->update([
            'time_limit_minutes' => $request->input('time_limit_minutes') ?: null,
            'max_attempts' => $request->integer('max_attempts'),
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'pass_score' => $request->input('pass_score') !== null && $request->input('pass_score') !== ''
                ? $request->float('pass_score')
                : null,
        ]);

        return redirect()
            ->route('teacher.activities.show', $activity)
            ->with('status', 'Configuración de la evaluación guardada.');
    }
}
