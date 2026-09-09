<?php

namespace App\Services\Reports;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Activity;
use App\Models\AiConversation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\ImmersiveSession;
use App\Models\Recommendation;
use App\Models\Subject;
use App\Models\User;
use App\Services\Analytics\CourseAnalyticsService;
use App\Services\Reports\Contracts\Report;

/**
 * Indicadores institucionales: KPIs de toda la plataforma y una fila resumen
 * por curso activo (avance medio y promedio).
 */
class InstitutionalReport implements Report
{
    public function __construct(private readonly CourseAnalyticsService $analytics) {}

    public function key(): string
    {
        return 'indicadores-institucionales';
    }

    public function title(): string
    {
        return 'Indicadores institucionales DSLE';
    }

    public function meta(): array
    {
        return ['Generado' => now()->format('d/m/Y H:i')];
    }

    public function headings(): array
    {
        return ['Curso', 'Código', 'Asignaturas', 'Inscritos', 'Avance medio %', 'Promedio %'];
    }

    public function rows(): array
    {
        return Course::query()
            ->where('status', AcademicStatus::Active->value)
            ->withCount(['subjects', 'enrollments'])
            ->orderBy('code')
            ->get()
            ->map(function (Course $course) {
                $overview = $this->analytics->courseOverview($course);

                return [
                    $course->name,
                    $course->code,
                    (string) $course->subjects_count,
                    (string) $course->enrollments_count,
                    Fmt::num($overview['average_progress']),
                    $overview['average'] !== null ? Fmt::num($overview['average']) : '—',
                ];
            })
            ->all();
    }

    public function summary(): array
    {
        $activeStudents = Enrollment::where('status', EnrollmentStatus::Active->value)
            ->distinct('student_id')->count('student_id');

        return [
            'Cursos (activos)' => Course::count().' ('.Course::where('status', AcademicStatus::Active->value)->count().')',
            'Asignaturas' => (string) Subject::count(),
            'Estudiantes con inscripción activa' => (string) $activeStudents,
            'Docentes con asignatura' => (string) Subject::whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id'),
            'Actividades publicadas' => (string) Activity::where('is_published', true)->count(),
            'Calificaciones registradas' => (string) Grade::count(),
            'Recomendaciones abiertas' => (string) Recommendation::whereIn('status', ['pending', 'accepted'])->count(),
            'Sesiones inmersivas completadas' => (string) ImmersiveSession::where('status', 'completed')->count(),
            'Conversaciones con el asistente' => (string) AiConversation::count(),
            'Usuarios activos' => (string) User::where('status', 'active')->count(),
        ];
    }
}
