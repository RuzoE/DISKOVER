<?php

namespace App\Http\Controllers\Student;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = $request->user()
            ->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->withCount(['subjects' => fn ($q) => $q->where('status', AcademicStatus::Active->value)])
            ->orderBy('name')
            ->get();

        return view('student.courses.index', ['courses' => $courses]);
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($request->user()->isEnrolledIn($course), 403);

        $course->load([
            'subjects' => fn ($q) => $q->where('status', AcademicStatus::Active->value),
            'subjects.teacher',
        ]);

        return view('student.courses.show', ['course' => $course]);
    }
}
