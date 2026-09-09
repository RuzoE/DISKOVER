<?php

namespace App\Http\Controllers\Student;

use App\Enums\AcademicStatus;
use App\Http\Controllers\Controller;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use App\Services\Immersive\ImmersiveSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Área del estudiante para las experiencias inmersivas: listado de las
 * disponibles en sus asignaturas, lanzamiento y historial de sesiones.
 */
class ImmersiveController extends Controller
{
    public function __construct(private readonly ImmersiveSessionService $sessions) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        $subjectIds = $student->enrolledCourses()
            ->wherePivotIn('status', ['active', 'completed'])
            ->get()
            ->flatMap(fn ($course) => $course->subjects()->where('status', AcademicStatus::Active->value)->pluck('id'))
            ->all();

        $experiences = ImmersiveExperience::query()
            ->where('status', AcademicStatus::Active->value)
            ->whereHas('subjects', fn ($q) => $q->whereIn('subjects.id', $subjectIds))
            ->with('subjects')
            ->get();

        return view('immersive.student.index', [
            'experiences' => $experiences,
            'sessions' => $student->immersiveSessions()->with('experience')->limit(15)->get(),
        ]);
    }

    public function launch(Request $request, ImmersiveExperience $experience): RedirectResponse
    {
        $student = $request->user();

        abort_unless($this->studentCanAccess($student, $experience), 403);

        $session = $this->sessions->startOrResume($experience, $student);

        return redirect()->route('student.immersive.session', $session);
    }

    public function session(Request $request, ImmersiveSession $session): View
    {
        abort_unless($session->student_id === $request->user()->id, 403);

        $session->load('experience');

        return view('immersive.student.session', ['session' => $session]);
    }

    public function abandon(Request $request, ImmersiveSession $session): RedirectResponse
    {
        abort_unless($session->student_id === $request->user()->id, 403);

        $this->sessions->abandon($session);

        return redirect()
            ->route('student.immersive.index')
            ->with('status', 'Sesión cerrada.');
    }

    private function studentCanAccess($student, ImmersiveExperience $experience): bool
    {
        if (! $experience->isActive()) {
            return false;
        }

        return $experience->subjects()
            ->whereHas('course', fn ($q) => $q->whereHas('enrollments', fn ($e) => $e->where('student_id', $student->id)
                ->whereIn('status', ['active', 'completed'])))
            ->where('subjects.status', AcademicStatus::Active->value)
            ->exists();
    }
}
