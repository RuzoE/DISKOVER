<?php

namespace App\Http\Controllers\Student;

use App\Enums\AcademicStatus;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\Analytics\ProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __invoke(Request $request, Subject $subject, ProgressCalculator $progress): View
    {
        $student = $request->user();
        $subject->loadMissing('course');

        abort_unless($student->isEnrolledIn($subject->course), 403);
        abort_unless(
            $subject->status === AcademicStatus::Active && $subject->course->isOpenForStudents(),
            404,
        );

        $contents = $subject->contents()->where('is_published', true)->get();
        $activities = $subject->activities()->where('is_published', true)->get();

        return view('student.subjects.show', [
            'subject' => $subject->load('teacher'),
            'contents' => $contents,
            'activities' => $activities,
            'completedIds' => $student->completedContents()->pluck('content_id')->all(),
            'progress' => $progress->forSubject($student, $subject),
        ]);
    }
}
