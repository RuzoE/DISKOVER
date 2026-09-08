<?php

namespace Tests\Feature\Tracking;

use App\Enums\LearningEventType;
use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use App\Services\Academic\EnrollmentService;
use App\Services\Academic\GradeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_enrolling_records_a_learning_event(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        app(EnrollmentService::class)->enroll($course, $student);

        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'type' => LearningEventType::Enrolled->value,
        ]);
    }

    public function test_marking_content_complete_records_an_event(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $content = Content::factory()->for($subject)->create(['is_published' => true]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->actingAs($student)
            ->post(route('student.contents.complete', $content))
            ->assertRedirect();

        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'type' => LearningEventType::ContentCompleted->value,
        ]);
    }

    public function test_submitting_and_grading_record_events(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'due_at' => now()->addWeek()]);
        $q = Question::factory()->for($activity->evaluation)->withOptions(2, 0)
            ->create(['type' => QuestionType::Single->value, 'score' => 1]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $attempts = app(AttemptService::class);
        $attempt = $attempts->startOrResume($activity->evaluation, $student);
        $attempts->submit($attempt, [$q->id => $q->options->firstWhere('is_correct', true)->id]);

        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'activity_id' => $activity->id,
            'type' => LearningEventType::AttemptSubmitted->value,
        ]);
        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'activity_id' => $activity->id,
            'type' => LearningEventType::ActivityGraded->value,
        ]);
    }

    public function test_manual_grade_records_a_graded_event(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        app(GradeService::class)->setManual($activity, $student, 90, null, $teacher);

        $this->assertDatabaseHas('learning_events', [
            'user_id' => $student->id,
            'activity_id' => $activity->id,
            'type' => LearningEventType::ActivityGraded->value,
        ]);
    }
}
