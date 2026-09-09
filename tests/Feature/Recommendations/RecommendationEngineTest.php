<?php

namespace Tests\Feature\Recommendations;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\QuestionType;
use App\Enums\RecommendationType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use App\Services\Recommendations\RecommendationEngine;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function enrolledStudent(Course $course): User
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return $student;
    }

    /**
     * @return Collection<string, RecommendationDraft>
     */
    private function drafts(User $student)
    {
        return app(RecommendationEngine::class)->run($student);
    }

    public function test_overdue_activity_produces_a_high_priority_recommendation(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        Activity::factory()->for($subject)->create(['is_published' => true, 'due_at' => now()->subWeek()]);
        $student = $this->enrolledStudent($course);

        $drafts = $this->drafts($student);

        $this->assertTrue($drafts->contains(fn ($d) => $d->type === RecommendationType::OverdueAlert));
    }

    public function test_upcoming_activity_within_a_week_is_recommended(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        Activity::factory()->for($subject)->create(['is_published' => true, 'due_at' => now()->addDays(3)]);
        $student = $this->enrolledStudent($course);

        $this->assertTrue($this->drafts($student)->contains(fn ($d) => $d->type === RecommendationType::CompletePending));
    }

    public function test_weak_subject_below_threshold_is_recommended(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['name' => 'Álgebra']);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100, 'due_at' => now()->addWeek()]);
        $student = $this->enrolledStudent($course);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 35]);

        $drafts = $this->drafts($student);
        $this->assertTrue($drafts->contains(fn ($d) => $d->type === RecommendationType::FocusSubject && $d->subjectId === $subject->id));
    }

    public function test_retry_recommendation_when_quiz_failed_with_attempts_left(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'max_score' => 100, 'due_at' => now()->addWeek()]);
        $activity->evaluation->update(['max_attempts' => 3, 'pass_score' => 60]);
        $q = Question::factory()->for($activity->evaluation)->withOptions(2, 0)->create(['type' => QuestionType::Single->value, 'score' => 1]);

        $student = $this->enrolledStudent($course);
        $service = app(AttemptService::class);
        $attempt = $service->startOrResume($activity->evaluation, $student);
        $service->submit($attempt, [$q->id => $q->options->firstWhere('is_correct', false)->id]); // 0 % -> graded

        $this->assertTrue($this->drafts($student)->contains(fn ($d) => $d->type === RecommendationType::RetryEvaluation));
    }

    public function test_no_retry_recommendation_when_no_attempts_left(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'max_score' => 100, 'due_at' => now()->addWeek()]);
        $activity->evaluation->update(['max_attempts' => 1, 'pass_score' => 60]);
        $q = Question::factory()->for($activity->evaluation)->withOptions(2, 0)->create(['type' => QuestionType::Single->value, 'score' => 1]);

        $student = $this->enrolledStudent($course);
        $service = app(AttemptService::class);
        $attempt = $service->startOrResume($activity->evaluation, $student);
        $service->submit($attempt, [$q->id => $q->options->firstWhere('is_correct', false)->id]);

        $this->assertFalse($this->drafts($student)->contains(fn ($d) => $d->type === RecommendationType::RetryEvaluation));
    }

    public function test_positive_reinforcement_when_everything_is_on_track(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100, 'due_at' => now()->addWeek()]);
        $content = Content::factory()->for($subject)->create(['is_published' => true]);
        $student = $this->enrolledStudent($course);
        $student->completedContents()->attach($content->id, ['completed_at' => now()]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 95]);

        $this->assertTrue($this->drafts($student)->contains(fn ($d) => $d->type === RecommendationType::Positive));
    }

    public function test_engine_returns_nothing_for_a_non_student(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->assertCount(0, $this->drafts($teacher));
    }
}
