<?php

namespace Tests\Feature\Assessment;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AttemptFlowTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    /**
     * Crea un quiz publicado con 1 pregunta de opción única (2 pts) y
     * devuelve [activity, question, enrolledStudent].
     */
    private function scenario(int $maxAttempts = 1): array
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'due_at' => now()->addWeek()]);
        $activity->evaluation->update(['max_attempts' => $maxAttempts]);

        $question = Question::factory()
            ->for($activity->evaluation)
            ->withOptions(3, 1)
            ->create(['type' => QuestionType::Single->value, 'score' => 2]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return [$activity, $question, $student];
    }

    public function test_student_starts_and_submits_an_attempt_with_auto_grading(): void
    {
        [$activity, $question, $student] = $this->scenario();
        $correctId = $question->options->firstWhere('is_correct', true)->id;

        $this->actingAs($student)
            ->post(route('student.activities.attempts.store', $activity))
            ->assertRedirect();

        $attempt = $student->attempts()->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [$question->id => $correctId],
            ])
            ->assertRedirect(route('student.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Graded, $attempt->status);
        $this->assertEquals(2.0, (float) $attempt->score);
        $this->assertDatabaseHas('grades', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'source' => 'auto',
        ]);
    }

    public function test_wrong_answer_scores_zero(): void
    {
        [$activity, $question, $student] = $this->scenario();
        $wrongId = $question->options->firstWhere('is_correct', false)->id;

        $this->actingAs($student)->post(route('student.activities.attempts.store', $activity));
        $attempt = $student->attempts()->firstOrFail();

        $this->actingAs($student)->post(route('student.attempts.submit', $attempt), [
            'answers' => [$question->id => $wrongId],
        ]);

        $this->assertEquals(0.0, (float) $attempt->fresh()->score);
    }

    public function test_max_attempts_is_enforced(): void
    {
        [$activity, $question, $student] = $this->scenario(1);
        $correctId = $question->options->firstWhere('is_correct', true)->id;

        $this->actingAs($student)->post(route('student.activities.attempts.store', $activity));
        $attempt = $student->attempts()->firstOrFail();
        $this->actingAs($student)->post(route('student.attempts.submit', $attempt), [
            'answers' => [$question->id => $correctId],
        ]);

        $this->actingAs($student)
            ->post(route('student.activities.attempts.store', $activity))
            ->assertSessionHasErrors('attempt');

        $this->assertSame(1, $student->attempts()->count());
    }

    public function test_student_not_enrolled_cannot_start_attempt(): void
    {
        [$activity] = $this->scenario();
        $outsider = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($outsider)
            ->post(route('student.activities.attempts.store', $activity))
            ->assertForbidden();
    }

    public function test_attempt_cannot_be_started_when_activity_is_closed(): void
    {
        [$activity, , $student] = $this->scenario();
        $activity->update(['due_at' => now()->subDay()]);

        $this->actingAs($student)
            ->post(route('student.activities.attempts.store', $activity))
            ->assertForbidden();
    }
}
