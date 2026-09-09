<?php

namespace Tests\Feature\Dashboard;

use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_sees_the_admin_dashboard(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de administración')
            ->assertSee('Usuarios por rol');
    }

    public function test_coordinator_sees_the_coordinator_dashboard(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        Subject::factory()->create(['teacher_id' => null, 'name' => 'Sin docente demo']);

        $this->actingAs($coordinator)->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de coordinación')
            ->assertSee('Asignaturas sin docente')
            ->assertSee('Sin docente demo');
    }

    public function test_student_dashboard_lists_upcoming_activities(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        Activity::factory()->for($subject)->create([
            'is_published' => true,
            'title' => 'Entrega próxima demo',
            'due_at' => now()->addDays(3),
        ]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->actingAs($student)->get('/dashboard')
            ->assertOk()
            ->assertSee('Próximas entregas')
            ->assertSee('Entrega próxima demo');
    }

    public function test_teacher_dashboard_shows_attempts_pending_review(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'due_at' => now()->addWeek()]);

        Question::factory()->for($activity->evaluation)->withOptions(2, 0)
            ->create(['type' => QuestionType::Single->value, 'score' => 2]);
        $open = Question::factory()->for($activity->evaluation)
            ->create(['type' => QuestionType::Open->value, 'score' => 3]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $service = app(AttemptService::class);
        $attempt = $service->startOrResume($activity->evaluation, $student);
        $service->submit($attempt, [$open->id => 'texto']);

        $this->actingAs($teacher)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pendiente de calificar')
            ->assertSee($student->name);
    }

    public function test_role_priority_admin_over_teacher(): void
    {
        $user = User::factory()->create();
        $user->syncRoles([RoleSlug::Teacher->value, RoleSlug::Admin->value]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de administración');
    }

    public function test_user_without_role_sees_fallback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Sin roles asignados');
    }
}
