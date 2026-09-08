<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Analytics\PerformanceAnalyticsService;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    public function __construct(private readonly PerformanceAnalyticsService $performance) {}

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        return view('analytics.course', [
            'course' => $course,
            'report' => $this->performance->courseReport($course),
        ]);
    }
}
