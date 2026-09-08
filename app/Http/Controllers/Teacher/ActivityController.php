<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreActivityRequest;
use App\Http\Requests\Academic\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Subject;
use App\Services\Academic\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(private readonly ActivityService $activities) {}

    public function index(Subject $subject): View
    {
        $this->authorize('view', $subject); // SubjectPolicy: titular o coordinación

        return view('teacher.activities.index', [
            'subject' => $subject->load('course'),
            'activities' => $subject->activities()->withCount('grades')->get(),
        ]);
    }

    public function create(Subject $subject): View
    {
        $this->authorize('create', [Activity::class, $subject]);

        return view('teacher.activities.create', [
            'subject' => $subject->load('course'),
            'types' => ActivityType::options(),
        ]);
    }

    public function store(StoreActivityRequest $request, Subject $subject): RedirectResponse
    {
        $activity = $this->activities->create($subject, $request->validated());

        return redirect()
            ->route('teacher.activities.show', $activity)
            ->with('status', 'Actividad creada.');
    }

    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $activity->load(['subject.course', 'evaluation.questions']);

        return view('teacher.activities.show', ['activity' => $activity]);
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('teacher.activities.edit', [
            'activity' => $activity->load('subject.course'),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->activities->update($activity, $request->validated());

        return redirect()
            ->route('teacher.activities.show', $activity)
            ->with('status', 'Actividad actualizada.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $subjectId = $activity->subject_id;
        $this->activities->delete($activity);

        return redirect()
            ->route('teacher.subjects.activities.index', $subjectId)
            ->with('status', 'Actividad eliminada.');
    }
}
