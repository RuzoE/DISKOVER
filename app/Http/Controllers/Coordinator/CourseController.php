<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\AcademicStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreCourseRequest;
use App\Http\Requests\Academic\UpdateCourseRequest;
use App\Models\Course;
use App\Services\Academic\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $filters = $request->only('search', 'status');

        $courses = Course::query()
            ->withCount(['subjects', 'enrollments'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.courses.index', [
            'courses' => $courses,
            'filters' => $filters,
            'statuses' => AcademicStatus::options(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        return view('coordinator.courses.create', [
            'statuses' => AcademicStatus::options(),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->create($request->validated());

        return redirect()
            ->route('coordinator.courses.show', $course)
            ->with('status', 'Curso creado correctamente.');
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load([
            'subjects.teacher',
            'enrollments.student',
        ]);

        return view('coordinator.courses.show', ['course' => $course]);
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        return view('coordinator.courses.edit', [
            'course' => $course,
            'statuses' => AcademicStatus::options(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->update($course, $request->validated());

        return redirect()
            ->route('coordinator.courses.show', $course)
            ->with('status', 'Curso actualizado correctamente.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $this->courses->delete($course);

        return redirect()
            ->route('coordinator.courses.index')
            ->with('status', 'Curso eliminado correctamente.');
    }
}
