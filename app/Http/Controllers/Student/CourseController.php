<?php

namespace App\Http\Controllers\Student;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Analytics\ProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private readonly ProgressCalculator $progress) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        $courses = $student
            ->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->withCount(['subjects' => fn ($q) => $q->where('status', AcademicStatus::Active->value)])
            ->orderBy('name')
            ->get();

        $progressByCourse = $courses->mapWithKeys(
            fn (Course $course) => [$course->id => $this->progress->forCourse($student, $course)]
        );

        return view('student.courses.index', [
            'courses' => $courses,
            'progressByCourse' => $progressByCourse,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        $student = $request->user();

        abort_unless($student->isEnrolledIn($course), 403);

        $course->load([
            'subjects' => fn ($q) => $q->where('status', AcademicStatus::Active->value),
            'subjects.teacher',
        ]);

        return view('student.courses.show', [
            'course' => $course,
            'progress' => $this->progress->forCourse($student, $course),
        ]);
    }
}
