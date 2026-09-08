<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\Analytics\CourseAnalyticsService;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(private readonly CourseAnalyticsService $analytics) {}

    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject); // titular de la asignatura o coordinación

        return view('teacher.progress.show', [
            'subject' => $subject->load('course'),
            'overview' => $this->analytics->subjectOverview($subject),
        ]);
    }
}
