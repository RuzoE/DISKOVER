<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\AcademicStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSubjectRequest;
use App\Http\Requests\Academic\UpdateSubjectRequest;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjects) {}

    public function index(Course $course): View
    {
        $this->authorize('viewAny', Subject::class);

        return view('coordinator.subjects.index', [
            'course' => $course,
            'subjects' => $course->subjects()->with('teacher')->withCount('contents')->get(),
        ]);
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Subject::class);

        return view('coordinator.subjects.create', [
            'course' => $course,
            'teachers' => $this->teacherOptions(),
            'statuses' => AcademicStatus::options(),
        ]);
    }

    public function store(StoreSubjectRequest $request, Course $course): RedirectResponse
    {
        $this->subjects->create($course, $request->validated());

        return redirect()
            ->route('coordinator.courses.subjects.index', $course)
            ->with('status', 'Asignatura creada correctamente.');
    }

    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject);

        $subject->load(['course', 'teacher', 'contents']);

        return view('coordinator.subjects.show', ['subject' => $subject]);
    }

    public function edit(Subject $subject): View
    {
        $this->authorize('update', $subject);

        return view('coordinator.subjects.edit', [
            'subject' => $subject->load('course'),
            'teachers' => $this->teacherOptions(),
            'statuses' => AcademicStatus::options(),
        ]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjects->update($subject, $request->validated());

        return redirect()
            ->route('coordinator.courses.subjects.index', $subject->course_id)
            ->with('status', 'Asignatura actualizada correctamente.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $courseId = $subject->course_id;
        $this->subjects->delete($subject);

        return redirect()
            ->route('coordinator.courses.subjects.index', $courseId)
            ->with('status', 'Asignatura eliminada correctamente.');
    }

    /**
     * @return array<int, string> [id => nombre]
     */
    private function teacherOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Teacher->value))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
