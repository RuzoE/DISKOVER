<?php

namespace Tests\Feature\Immersive;

use App\Enums\ImmersiveSessionStatus;
use App\Enums\LearningEventType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImmersiveApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function startedSession(?Activity $activity = null): ImmersiveSession
    {
        $experience = ImmersiveExperience::factory()->create([
            'max_score' => 200,
            'activity_id' => $activity?->id,
        ]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        return ImmersiveSession::factory()->create([
            'immersive_experience_id' => $experience->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_get_session_returns_config_and_student(): void
    {
        $session = $this->startedSession();

        $this->getJson("/api/v1/immersive/sessions/{$session->launch_token}")
            ->assertOk()
            ->assertJsonPath('experience.slug', $session->experience->slug)
            ->assertJsonPath('experience.max_score', 200)
            ->assertJsonPath('student.id', $session->student_id);
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->getJson('/api/v1/immersive/sessions/nope')->assertNotFound();
    }

    public function test_complete_records_score_and_grades_the_linked_activity(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->create(['is_published' => true, 'max_score' => 100]);

        $experience = ImmersiveExperience::factory()->create(['max_score' => 200, 'activity_id' => $activity->id]);
        $experience->subjects()->attach($subject->id);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
        $session = ImmersiveSession::factory()->create([
            'immersive_experience_id' => $experience->id,
            'student_id' => $student->id,
        ]);

        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/complete", [
            'score' => 150,
            'payload' => ['level' => 3],
        ])->assertOk()->assertJsonPath('percentage', 75);

        $session->refresh();
        $this->assertSame(ImmersiveSessionStatus::Completed, $session->status);
        $this->assertEquals(150.0, (float) $session->score);
        $this->assertSame(['level' => 3], $session->payload);

        // Nota escalada a activity.max_score (100): 150/200 * 100 = 75
        $this->assertDatabaseHas('grades', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'score' => 75.00,
            'source' => 'immersive',
        ]);
        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'type' => LearningEventType::ActivityGraded->value,
        ]);
    }

    public function test_completing_twice_is_rejected(): void
    {
        $session = $this->startedSession();

        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/complete", ['score' => 10])->assertOk();
        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/complete", ['score' => 20])->assertStatus(422);
    }

    public function test_expired_session_cannot_be_completed(): void
    {
        $session = ImmersiveSession::factory()->stale()->create();

        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/complete", ['score' => 10])
            ->assertStatus(422);

        $this->assertSame(ImmersiveSessionStatus::Expired, $session->fresh()->status);
    }

    public function test_score_is_required(): void
    {
        $session = $this->startedSession();

        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/complete", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('score');
    }

    public function test_abandon_marks_the_session(): void
    {
        $session = $this->startedSession();

        $this->postJson("/api/v1/immersive/sessions/{$session->launch_token}/abandon")
            ->assertOk()
            ->assertJsonPath('status', 'abandoned');
    }
}
