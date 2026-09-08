<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Analytics\StudentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(private readonly StudentAnalyticsService $analytics) {}

    public function show(Request $request, Course $course): View
    {
        $student = $request->user();

        abort_unless($student->isEnrolledIn($course), 403);

        return view('student.progress.show', [
            'course' => $course,
            'progress' => $this->analytics->courseProgress($student, $course),
        ]);
    }
}
