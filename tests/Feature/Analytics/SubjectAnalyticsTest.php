<?php

namespace Tests\Feature\Analytics;

use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use App\Services\Analytics\AssessmentAnalyticsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_teacher_owner_sees_subject_analytics_with_at_risk_student(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);

        $weak = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Ana Riesgo']);
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $weak->id]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $weak->id, 'score' => 30]);

        $this->actingAs($teacher)->get(route('analytics.subject', $subject))
            ->assertOk()
            ->assertSee('Analítica de '.$subject->name)
            ->assertSee('Ana Riesgo');
    }

    public function test_non_owner_teacher_is_forbidden(): void
    {
        $subject = Subject::factory()->create(['teacher_id' => User::factory()->withRole(RoleSlug::Teacher->value)->create()->id]);
        $intruder = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($intruder)->get(route('analytics.subject', $subject))->assertForbidden();
    }

    public function test_difficult_questions_are_detected(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->quiz()->create(['is_published' => true, 'due_at' => now()->addWeek()]);
        $q = Question::factory()->for($activity->evaluation)->withOptions(3, 0)
            ->create(['type' => QuestionType::Single->value, 'score' => 1, 'statement' => 'Pregunta trampa']);

        // Tres estudiantes fallan la pregunta.
        foreach (range(1, 3) as $_) {
            $student = User::factory()->withRole(RoleSlug::Student->value)->create();
            Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
            $service = app(AttemptService::class);
            $attempt = $service->startOrResume($activity->evaluation, $student);
            $service->submit($attempt, [$q->id => $q->options->firstWhere('is_correct', false)->id]);
        }

        $difficult = app(AssessmentAnalyticsService::class)->difficultQuestions($subject);

        $this->assertCount(1, $difficult);
        $this->assertSame('Pregunta trampa', $difficult->first()['statement']);
        $this->assertSame(0.0, $difficult->first()['success_rate']);
    }

    public function test_student_cannot_open_subject_analytics(): void
    {
        $subject = Subject::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)->get(route('analytics.subject', $subject))->assertForbidden();
    }
}
