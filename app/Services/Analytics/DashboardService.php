<?php

namespace App\Services\Analytics;

use App\Enums\AcademicStatus;
use App\Enums\AttemptStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LearningEvent;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Services\Recommendations\RecommendationService;

/**
 * Datos agregados para los paneles de inicio, uno por rol. Reutiliza los
 * servicios de analítica de la Fase 4 y añade las consultas específicas de
 * cada panel.
 */
class DashboardService
{
    public function __construct(
        private readonly StudentAnalyticsService $student,
        private readonly RecommendationService $recommendations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(User $user): array
    {
        $courseIds = $user->enrollments()
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->pluck('course_id');

        $upcoming = Activity::query()
            ->whereHas('subject', fn ($q) => $q->whereIn('course_id', $courseIds)
                ->where('status', AcademicStatus::Active->value))
            ->where('is_published', true)
            ->whereDoesntHave('grades', fn ($q) => $q->where('student_id', $user->id))
            ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', now()->subDay()))
            ->with('subject.course')
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->limit(6)
            ->get();

        $this->recommendations->generateFor($user);

        return [
            'profile' => $this->student->profile($user),
            'history' => $this->student->history($user, 8),
            'upcoming' => $upcoming,
            'recommendations' => $this->recommendations->openFor($user)->take(3),
            'recommendation_stats' => $this->recommendations->stats($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forTeacher(User $user): array
    {
        $subjects = $user->subjectsTeaching()
            ->with('course')
            ->withCount([
                'activities as activities_count' => fn ($q) => $q->where('is_published', true),
                'contents as contents_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->orderBy('name')
            ->get();

        $subjectIds = $subjects->pluck('id');

        $studentsCount = Enrollment::query()
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->whereIn('course_id', $subjects->pluck('course_id')->unique())
            ->distinct('student_id')
            ->count('student_id');

        $pendingReview = Attempt::query()
            ->where('status', AttemptStatus::Submitted->value)
            ->whereHas('evaluation.activity', fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->with(['student', 'evaluation.activity'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return [
            'subjects' => $subjects,
            'stats' => [
                'subjects' => $subjects->count(),
                'students' => $studentsCount,
                'activities' => (int) $subjects->sum('activities_count'),
                'pending_review' => $pendingReview->count(),
            ],
            'pending_review' => $pendingReview,
            'history' => LearningEvent::query()
                ->whereIn('subject_id', $subjectIds)
                ->with(['user', 'course'])
                ->latest('occurred_at')
                ->limit(12)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forCoordinator(User $user): array
    {
        return [
            'stats' => [
                'courses' => Course::count(),
                'courses_active' => Course::where('status', AcademicStatus::Active->value)->count(),
                'subjects' => Subject::count(),
                'students' => Enrollment::where('status', EnrollmentStatus::Active->value)->distinct('student_id')->count('student_id'),
                'teachers' => Subject::whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id'),
            ],
            'courses' => Course::query()
                ->where('status', AcademicStatus::Active->value)
                ->withCount(['subjects', 'enrollments'])
                ->orderBy('code')
                ->limit(12)
                ->get(),
            'subjects_without_teacher' => Subject::query()
                ->whereNull('teacher_id')
                ->with('course')
                ->orderBy('name')
                ->limit(10)
                ->get(),
            'history' => LearningEvent::query()
                ->with(['user', 'course'])
                ->latest('occurred_at')
                ->limit(15)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forAdmin(User $user): array
    {
        $byStatus = User::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'stats' => [
                'users' => (int) $byStatus->sum(),
                'users_active' => (int) ($byStatus[UserStatus::Active->value] ?? 0),
                'users_suspended' => (int) ($byStatus[UserStatus::Suspended->value] ?? 0),
                'roles' => Role::count(),
                'courses' => Course::count(),
                'subjects' => Subject::count(),
                'enrollments' => Enrollment::where('status', EnrollmentStatus::Active->value)->count(),
                'activities' => Activity::count(),
                'grades' => Grade::count(),
            ],
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'recent_users' => User::with('roles')->latest()->limit(6)->get(),
            'history' => LearningEvent::query()
                ->with(['user', 'course'])
                ->latest('occurred_at')
                ->limit(15)
                ->get(),
        ];
    }

    /**
     * Rol prioritario para elegir el panel (admin > coordinación > docente > estudiante).
     */
    public function primaryRole(User $user): ?RoleSlug
    {
        foreach ([RoleSlug::Admin, RoleSlug::Coordinator, RoleSlug::Teacher, RoleSlug::Student] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }
}
