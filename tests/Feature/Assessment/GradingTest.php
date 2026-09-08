<?php

namespace Tests\Feature\Assessment;

use App\Enums\AttemptStatus;
use App\Enums\GradeSource;
use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use App\Services\Academic\GradeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_teacher_grades_a_task_manually(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id, 'teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.activities.grades.store', $activity), [
                'student_id' => $student->id,
                'score' => 87.5,
                'feedback' => 'Buen trabajo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'score' => 87.50,
            'source' => GradeSource::Manual->value,
            'graded_by' => $teacher->id,
        ]);
    }

    public function test_manual_grade_over_the_max_is_rejected(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 20]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.grades.store', $activity), [
                'student_id' => $student->id,
                'score' => 25,
            ])
            ->assertSessionHasErrors('score');
    }

    public function test_open_answer_review_consolidates_the_grade(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id, 'teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'max_score' => 10, 'due_at' => now()->addWeek()]);

        $closed = Question::factory()->for($activity->evaluation)->withOptions(2, 0)
            ->create(['type' => QuestionType::Single->value, 'score' => 4]);
        $open = Question::factory()->for($activity->evaluation)
            ->create(['type' => QuestionType::Open->value, 'score' => 6]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $service = app(AttemptService::class);
        $attempt = $service->startOrResume($activity->evaluation, $student);
        $service->submit($attempt, [
            $closed->id => $closed->options->firstWhere('is_correct', true)->id,
            $open->id => 'respuesta redactada',
        ]);
        $attempt->refresh();

        // Cerrada correcta = 4; abierta pendiente -> submitted, sin nota final aún.
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertDatabaseMissing('grades', ['activity_id' => $activity->id, 'student_id' => $student->id]);

        $openAnswer = $attempt->answers()->where('question_id', $open->id)->firstOrFail();

        $this->actingAs($teacher)
            ->put(route('teacher.attempts.review.update', $attempt), [
                'scores' => [$openAnswer->id => 6],
            ])
            ->assertRedirect();

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Graded, $attempt->status);
        $this->assertEquals(10.0, (float) $attempt->score);
        // Escala a activity.max_score (10) -> 10/10 == 10
        $this->assertDatabaseHas('grades', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'score' => 10.00,
            'source' => GradeSource::Auto->value,
        ]);
    }

    public function test_auto_sync_does_not_override_a_manual_grade(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id, 'teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'max_score' => 100, 'due_at' => now()->addWeek()]);
        $q = Question::factory()->for($activity->evaluation)->withOptions(2, 0)
            ->create(['type' => QuestionType::Single->value, 'score' => 1]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        app(GradeService::class)->setManual($activity, $student, 55, 'ajuste', $teacher);

        $service = app(AttemptService::class);
        $attempt = $service->startOrResume($activity->evaluation, $student);
        $service->submit($attempt, [$q->id => $q->options->firstWhere('is_correct', true)->id]);

        $grade = $activity->grades()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame(GradeSource::Manual, $grade->source);
        $this->assertEquals(55.0, (float) $grade->score);
    }
}
