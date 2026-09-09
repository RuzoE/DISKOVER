<?php

namespace Tests\Feature\Recommendations;

use App\Enums\RecommendationStatus;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Recommendation;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class RecommendationHttpTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function studentWithOverdue(): User
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        Activity::factory()->for($subject)->create([
            'is_published' => true,
            'title' => 'Tarea vencida demo',
            'due_at' => now()->subWeek(),
        ]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return $student;
    }

    public function test_index_generates_and_lists_recommendations(): void
    {
        $student = $this->studentWithOverdue();

        $this->actingAs($student)->get(route('student.recommendations.index'))
            ->assertOk()
            ->assertSee('Recomendaciones para ti')
            ->assertSee('Actividad vencida sin entregar');

        $this->assertGreaterThan(0, $student->recommendations()->count());
    }

    public function test_student_can_respond_to_a_recommendation(): void
    {
        $student = $this->studentWithOverdue();
        $this->actingAs($student)->get(route('student.recommendations.index'));
        $rec = $student->recommendations()->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.recommendations.respond', $rec), ['action' => 'dismiss'])
            ->assertRedirect(route('student.recommendations.index'));

        $rec->refresh();
        $this->assertSame(RecommendationStatus::Dismissed, $rec->status);
        $this->assertNotNull($rec->responded_at);
    }

    public function test_invalid_action_is_rejected(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        $rec = Recommendation::factory()->for($student)->create();

        $this->actingAs($student)
            ->post(route('student.recommendations.respond', $rec), ['action' => 'explode'])
            ->assertSessionHasErrors('action');
    }

    public function test_user_cannot_respond_to_another_users_recommendation(): void
    {
        $rec = Recommendation::factory()->create();
        $other = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($other)
            ->post(route('student.recommendations.respond', $rec), ['action' => 'complete'])
            ->assertForbidden();
    }

    public function test_teacher_cannot_open_the_recommendations_area(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($teacher)->get(route('student.recommendations.index'))->assertForbidden();
    }
}
