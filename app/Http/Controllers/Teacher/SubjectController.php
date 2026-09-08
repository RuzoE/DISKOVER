<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = $request->user()
            ->subjectsTeaching()
            ->with('course')
            ->withCount('contents')
            ->orderBy('name')
            ->get();

        return view('teacher.subjects.index', ['subjects' => $subjects]);
    }

    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject);

        $subject->load(['course', 'contents']);

        return view('teacher.subjects.show', ['subject' => $subject]);
    }
}
