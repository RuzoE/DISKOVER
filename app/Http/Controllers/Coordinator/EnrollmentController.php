<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreEnrollmentRequest;
use App\Http\Requests\Academic\UpdateEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Academic\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function index(Course $course): View
    {
        $this->authorize('viewAny', Enrollment::class);

        $enrolledIds = $course->enrollments()->pluck('student_id');

        return view('coordinator.enrollments.index', [
            'course' => $course,
            'enrollments' => $course->enrollments()->with('student')->latest()->get(),
            'statuses' => EnrollmentStatus::options(),
            'availableStudents' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Student->value))
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
        ]);
    }

    public function store(StoreEnrollmentRequest $request, Course $course): RedirectResponse
    {
        $student = User::findOrFail($request->integer('student_id'));

        $this->enrollments->assertStudent($student);
        $this->enrollments->enroll($course, $student);

        return redirect()
            ->route('coordinator.courses.enrollments.index', $course)
            ->with('status', "Se inscribió a {$student->name}.");
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->enrollments->updateStatus($enrollment, $request->validated());

        return redirect()
            ->route('coordinator.courses.enrollments.index', $enrollment->course_id)
            ->with('status', 'Inscripción actualizada.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('delete', $enrollment);

        $courseId = $enrollment->course_id;
        $this->enrollments->remove($enrollment);

        return redirect()
            ->route('coordinator.courses.enrollments.index', $courseId)
            ->with('status', 'Inscripción eliminada.');
    }
}
