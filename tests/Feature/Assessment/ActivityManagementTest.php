<?php

namespace Tests\Feature\Assessment;

use App\Enums\ActivityType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function teacherWithSubject(): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);

        return [$teacher, $subject];
    }

    public function test_teacher_creates_a_task_activity(): void
    {
        [$teacher, $subject] = $this->teacherWithSubject();

        $this->actingAs($teacher)
            ->post(route('teacher.subjects.activities.store', $subject), [
                'type' => ActivityType::Task->value,
                'title' => 'Ensayo',
                'max_score' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activities', ['subject_id' => $subject->id, 'title' => 'Ensayo', 'type' => 'task']);
    }

    public function test_creating_a_quiz_activity_also_creates_its_evaluation(): void
    {
        [$teacher, $subject] = $this->teacherWithSubject();

        $this->actingAs($teacher)
            ->post(route('teacher.subjects.activities.store', $subject), [
                'type' => ActivityType::Quiz->value,
                'title' => 'Examen 1',
                'max_score' => 20,
            ])
            ->assertRedirect();

        $activity = Activity::where('title', 'Examen 1')->firstOrFail();
        $this->assertNotNull($activity->evaluation);
    }

    public function test_teacher_cannot_manage_activities_of_another_teachers_subject(): void
    {
        [, $subject] = $this->teacherWithSubject();
        $intruder = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($intruder)
            ->get(route('teacher.subjects.activities.create', $subject))
            ->assertForbidden();
    }

    public function test_activity_type_is_not_changed_on_update(): void
    {
        [$teacher, $subject] = $this->teacherWithSubject();
        $activity = Activity::factory()->for($subject)->create(['type' => ActivityType::Task->value]);

        $this->actingAs($teacher)
            ->put(route('teacher.activities.update', $activity), [
                'type' => ActivityType::Quiz->value, // debe ignorarse
                'title' => 'Renombrada',
                'max_score' => 50,
            ])
            ->assertRedirect();

        $this->assertSame(ActivityType::Task, $activity->fresh()->type);
        $this->assertSame('Renombrada', $activity->fresh()->title);
    }

    public function test_student_cannot_reach_teacher_activity_area(): void
    {
        [, $subject] = $this->teacherWithSubject();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)
            ->get(route('teacher.subjects.activities.index', $subject))
            ->assertForbidden();
    }
}
