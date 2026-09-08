<?php

namespace App\Http\Controllers\Student;

use App\Enums\AcademicStatus;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __invoke(Request $request, Subject $subject): View
    {
        $subject->loadMissing('course');

        abort_unless($request->user()->isEnrolledIn($subject->course), 403);
        abort_unless(
            $subject->status === AcademicStatus::Active && $subject->course->isOpenForStudents(),
            404,
        );

        $contents = $subject->contents()->where('is_published', true)->get();

        return view('student.subjects.show', [
            'subject' => $subject->load('teacher'),
            'contents' => $contents,
        ]);
    }
}
