<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\Analytics\AssessmentAnalyticsService;
use App\Services\Analytics\CourseAnalyticsService;
use App\Services\Analytics\PerformanceAnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SubjectAnalyticsController extends Controller
{
    public function __construct(
        private readonly CourseAnalyticsService $courseAnalytics,
        private readonly AssessmentAnalyticsService $assessment,
        private readonly PerformanceAnalyticsService $performance,
    ) {}

    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject); // titular de la asignatura o coordinación

        $subject->load('course');
        $overview = $this->courseAnalytics->subjectOverview($subject);

        $studentSeries = collect($overview['rows'])->map(fn ($r) => [
            'label' => $r['student']->name,
            'value' => $r['progress']['average'] ?? 0,
            'has_data' => $r['progress']['average'] !== null,
        ])->all();

        $averages = collect($overview['rows'])
            ->pluck('progress.average')
            ->filter(fn ($v) => $v !== null);

        $atRisk = collect($overview['rows'])
            ->filter(fn ($r) => $r['progress']['average'] !== null
                && $r['progress']['average'] < (float) config('dsle.analytics.at_risk_threshold', 60))
            ->map(fn ($r) => ['name' => $r['student']->name, 'average' => $r['progress']['average']])
            ->sortBy('average')
            ->values();

        return view('analytics.subject', [
            'subject' => $subject,
            'overview' => $overview,
            'studentSeries' => $studentSeries,
            'distribution' => $this->performance->distribution($averages instanceof Collection ? $averages : collect($averages)),
            'atRisk' => $atRisk,
            'difficultQuestions' => $this->assessment->difficultQuestions($subject),
        ]);
    }
}
