<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Analytics\StudentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly StudentAnalyticsService $analytics) {}

    public function show(Request $request): View
    {
        $student = $request->user();

        return view('student.profile.show', [
            'profile' => $this->analytics->profile($student),
            'history' => $this->analytics->history($student, 40),
        ]);
    }
}
