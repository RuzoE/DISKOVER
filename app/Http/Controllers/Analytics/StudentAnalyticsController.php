<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\PerformanceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAnalyticsController extends Controller
{
    public function __construct(private readonly PerformanceAnalyticsService $performance) {}

    public function show(Request $request): View
    {
        return view('analytics.student', [
            'report' => $this->performance->studentReport($request->user()),
        ]);
    }
}
