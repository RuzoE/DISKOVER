<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreGradeRequest;
use App\Models\Activity;
use App\Models\Grade;
use App\Models\User;
use App\Services\Academic\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cuaderno de calificaciones de una actividad.
 */
class GradeController extends Controller
{
    public function __construct(private readonly GradeService $grades) {}

    public function index(Activity $activity): View
    {
        $this->authorize('manage', [Grade::class, $activity]);

        $activity->load('subject.course', 'evaluation');

        $students = $activity->subject->course
            ->students()
            ->wherePivotIn('status', ['active', 'completed'])
            ->orderBy('name')
            ->get();

        $gradesByStudent = $activity->grades()->get()->keyBy('student_id');

        $attemptsByStudent = collect();
        if ($activity->isQuiz() && $activity->evaluation) {
            $attemptsByStudent = $activity->evaluation->attempts()
                ->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::Graded->value])
                ->get()
                ->groupBy('student_id');
        }

        return view('teacher.grades.index', [
            'activity' => $activity,
            'students' => $students,
            'gradesByStudent' => $gradesByStudent,
            'attemptsByStudent' => $attemptsByStudent,
        ]);
    }

    public function store(StoreGradeRequest $request, Activity $activity): RedirectResponse
    {
        $student = User::findOrFail($request->integer('student_id'));

        $this->grades->setManual(
            $activity,
            $student,
            $request->float('score'),
            $request->input('feedback'),
            $request->user(),
        );

        return redirect()
            ->route('teacher.activities.gradebook', $activity)
            ->with('status', "Calificación guardada para {$student->name}.");
    }
}
