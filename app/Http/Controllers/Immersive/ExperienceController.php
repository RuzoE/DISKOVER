<?php

namespace App\Http\Controllers\Immersive;

use App\Enums\AcademicStatus;
use App\Enums\ImmersiveProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Immersive\StoreExperienceRequest;
use App\Http\Requests\Immersive\UpdateExperienceRequest;
use App\Models\Activity;
use App\Models\ImmersiveExperience;
use App\Models\Subject;
use App\Services\Immersive\ImmersiveExperienceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function __construct(private readonly ImmersiveExperienceService $experiences) {}

    public function index(): View
    {
        $this->authorize('viewAny', ImmersiveExperience::class);

        return view('immersive.experiences.index', [
            'experiences' => ImmersiveExperience::query()
                ->withCount(['sessions', 'subjects'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ImmersiveExperience::class);

        return view('immersive.experiences.create', $this->formData());
    }

    public function store(StoreExperienceRequest $request): RedirectResponse
    {
        $experience = $this->experiences->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('immersive.experiences.show', $experience)
            ->with('status', 'Experiencia registrada.');
    }

    public function show(ImmersiveExperience $experience): View
    {
        $this->authorize('view', $experience);

        $experience->load(['subjects.course', 'activity.subject', 'author']);

        return view('immersive.experiences.show', [
            'experience' => $experience,
            'sessions' => $experience->sessions()->with('student')->latest('started_at')->limit(20)->get(),
        ]);
    }

    public function edit(ImmersiveExperience $experience): View
    {
        $this->authorize('update', $experience);

        return view('immersive.experiences.edit', $this->formData() + [
            'experience' => $experience->load('subjects'),
        ]);
    }

    public function update(UpdateExperienceRequest $request, ImmersiveExperience $experience): RedirectResponse
    {
        $this->experiences->update($experience, $request->validated());

        return redirect()
            ->route('immersive.experiences.show', $experience)
            ->with('status', 'Experiencia actualizada.');
    }

    public function destroy(ImmersiveExperience $experience): RedirectResponse
    {
        $this->authorize('delete', $experience);

        $this->experiences->delete($experience);

        return redirect()
            ->route('immersive.experiences.index')
            ->with('status', 'Experiencia eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'providers' => ImmersiveProvider::options(),
            'statuses' => AcademicStatus::options(),
            'subjects' => Subject::query()->with('course')->orderBy('name')->get(),
            'activityOptions' => Activity::query()
                ->with('subject')
                ->orderBy('title')
                ->get()
                ->mapWithKeys(fn (Activity $a) => [$a->id => $a->title.' — '.$a->subject->name])
                ->all(),
        ];
    }
}
