<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Analytics\CourseAnalyticsService;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(private readonly CourseAnalyticsService $analytics) {}

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        return view('coordinator.progress.show', [
            'course' => $course,
            'overview' => $this->analytics->courseOverview($course),
        ]);
    }
}
