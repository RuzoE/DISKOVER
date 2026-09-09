<?php

namespace Tests\Feature\Analytics;

use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\Analytics\PerformanceAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CourseAnalyticsTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function courseWithData(): Course
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['name' => 'Redes']);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);

        foreach ([85, 45] as $score) {
            $student = User::factory()->withRole(RoleSlug::Student->value)->create();
            Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
            Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => $score]);
        }

        return $course;
    }

    public function test_coordinator_sees_course_analytics(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $course = $this->courseWithData();

        $this->actingAs($coordinator)->get(route('analytics.course', $course))
            ->assertOk()
            ->assertSee('Analítica de '.$course->name)
            ->assertSee('Redes')
            ->assertSee('Estudiantes en riesgo');
    }

    public function test_course_report_computes_subject_average_and_at_risk(): void
    {
        $course = $this->courseWithData();

        $report = app(PerformanceAnalyticsService::class)->courseReport($course);

        $this->assertSame(65.0, $report['course_average']); // (85 + 45) / 2
        $this->assertCount(1, $report['at_risk']);          // el de 45
        $this->assertSame('Redes', $report['by_subject'][0]['label']);
    }

    public function test_student_cannot_open_course_analytics(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        $course = Course::factory()->create();

        $this->actingAs($student)->get(route('analytics.course', $course))->assertForbidden();
    }
}
