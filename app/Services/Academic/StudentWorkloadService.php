<?php

namespace App\Services\Academic;

use App\Enums\AcademicStatus;
use App\Enums\ActivityType;
use App\Enums\AttemptStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Content;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Carga de trabajo de un estudiante: actividades sin calificar, contenidos sin
 * completar, calificaciones recientes e intentos en curso. Consulta SOLO datos
 * del propio estudiante; lo usan el asistente de IA y su capa de integridad.
 */
class StudentWorkloadService
{
    /**
     * Ids de las asignaturas activas de los cursos en los que está inscrito.
     *
     * @return Collection<int, int>
     */
    public function subjectIds(User $student): Collection
    {
        $courseIds = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->pluck('courses.id');

        return Subject::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', AcademicStatus::Active->value)
            ->pluck('id');
    }

    /**
     * Actividades publicadas sin calificación, ordenadas por fecha límite
     * (las vencidas primero; las que no tienen fecha, al final).
     * status: pending | overdue | submitted (cuestionario enviado sin nota).
     *
     * @return Collection<int, array{activity: Activity, status: string}>
     */
    public function pendingActivities(User $student, ?int $limit = null): Collection
    {
        $subjectIds = $this->subjectIds($student);

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        $gradedIds = $student->grades()->pluck('activity_id');

        $activities = Activity::query()
            ->with(['subject:id,name', 'evaluation:id,activity_id'])
            ->whereIn('subject_id', $subjectIds)
            ->where('is_published', true)
            ->whereNotIn('id', $gradedIds)
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderBy('id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();

        $submittedEvaluationIds = Attempt::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::Graded->value])
            ->whereIn('evaluation_id', $activities->pluck('evaluation.id')->filter())
            ->pluck('evaluation_id');

        return $activities->map(fn (Activity $activity) => [
            'activity' => $activity,
            'status' => match (true) {
                $activity->evaluation !== null && $submittedEvaluationIds->contains($activity->evaluation->id) => 'submitted',
                $activity->isPastDue() => 'overdue',
                default => 'pending',
            },
        ]);
    }

    /**
     * Contenidos publicados que el estudiante aún no ha completado.
     *
     * @return Collection<int, Content>
     */
    public function uncompletedContents(User $student, int $limit = 5): Collection
    {
        $subjectIds = $this->subjectIds($student);

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return Content::query()
            ->with('subject:id,name')
            ->whereIn('subject_id', $subjectIds)
            ->where('is_published', true)
            ->whereNotIn('id', $student->completedContents()->pluck('contents.id'))
            ->orderBy('subject_id')
            ->orderBy('position')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Grade>
     */
    public function recentGrades(User $student, int $limit = 3): Collection
    {
        return $student->grades()
            ->with('activity.subject:id,name')
            ->latest('graded_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Intento de evaluación que el estudiante está resolviendo ahora mismo
     * (en curso, con la evaluación abierta y sin tiempo agotado).
     */
    public function attemptInProgress(User $student): ?Attempt
    {
        return Attempt::query()
            ->with('evaluation.activity')
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::InProgress->value)
            ->latest('id')
            ->get()
            ->first(fn (Attempt $a) => $a->evaluation?->activity?->isOpenNow() && ! $a->deadlineReached());
    }

    /**
     * Evaluaciones (cuestionarios) abiertas ahora mismo en sus asignaturas,
     * con preguntas y opciones. Uso interno: nunca se envían al proveedor.
     *
     * @return Collection<int, Activity>
     */
    public function openQuizzes(User $student): Collection
    {
        return Activity::query()
            ->with('evaluation.questions.options')
            ->whereIn('subject_id', $this->subjectIds($student))
            ->where('type', ActivityType::Quiz->value)
            ->get()
            ->filter(fn (Activity $a) => $a->isOpenNow() && $a->evaluation !== null)
            ->values();
    }
}
