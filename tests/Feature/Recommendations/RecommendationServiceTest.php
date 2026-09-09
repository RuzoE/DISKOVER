<?php

namespace Tests\Feature\Recommendations;

use App\Enums\RecommendationStatus;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Recommendation;
use App\Models\Subject;
use App\Models\User;
use App\Services\Recommendations\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function scenarioWithOverdue(): array
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->create(['is_published' => true, 'due_at' => now()->subWeek()]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return [$student, $activity];
    }

    public function test_generation_is_idempotent(): void
    {
        [$student] = $this->scenarioWithOverdue();
        $service = app(RecommendationService::class);

        $first = $service->generateFor($student);
        $second = $service->generateFor($student);

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
        $this->assertSame($first, $student->recommendations()->count());
    }

    public function test_open_recommendation_is_resolved_when_its_condition_no_longer_applies(): void
    {
        [$student, $activity] = $this->scenarioWithOverdue();
        $service = app(RecommendationService::class);
        $service->generateFor($student);

        $rec = $student->recommendations()->where('type', 'overdue_alert')->firstOrFail();
        $this->assertSame(RecommendationStatus::Pending, $rec->status);

        // El estudiante entrega -> ya no está vencida sin calificar.
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 70]);
        $service->generateFor($student);

        $rec->refresh();
        $this->assertSame(RecommendationStatus::Completed, $rec->status);
        $this->assertNotNull($rec->responded_at);
    }

    public function test_dismissed_recommendation_is_not_resurrected(): void
    {
        [$student] = $this->scenarioWithOverdue();
        $service = app(RecommendationService::class);
        $service->generateFor($student);

        $rec = $student->recommendations()->firstOrFail();
        $service->respond($rec, RecommendationStatus::Dismissed);

        $service->generateFor($student); // condición sigue vigente

        $this->assertSame(RecommendationStatus::Dismissed, $rec->fresh()->status);
        $this->assertSame(1, $student->recommendations()->count());
    }

    public function test_respond_records_status_and_timestamp(): void
    {
        $rec = Recommendation::factory()->create();

        app(RecommendationService::class)->respond($rec, RecommendationStatus::Accepted);

        $rec->refresh();
        $this->assertSame(RecommendationStatus::Accepted, $rec->status);
        $this->assertNotNull($rec->responded_at);
    }

    public function test_non_student_gets_no_recommendations(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->assertSame(0, app(RecommendationService::class)->generateFor($teacher));
    }
}
