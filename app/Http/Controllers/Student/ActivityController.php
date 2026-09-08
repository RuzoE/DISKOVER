<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function show(Request $request, Activity $activity): View
    {
        $student = $request->user();
        $activity->load('subject.course', 'evaluation.questions');

        abort_unless($student->isEnrolledIn($activity->subject->course), 403);
        abort_unless($activity->is_published, 404);

        $grade = $activity->grades()->where('student_id', $student->id)->first();

        $attempts = collect();
        if ($activity->isQuiz() && $activity->evaluation) {
            $attempts = $activity->evaluation->attempts()
                ->where('student_id', $student->id)
                ->orderBy('number')
                ->get();
        }

        return view('student.activities.show', [
            'activity' => $activity,
            'grade' => $grade,
            'attempts' => $attempts,
        ]);
    }
}
