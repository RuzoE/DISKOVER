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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function studentWithGrades(): array
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['name' => 'Álgebra']);
        $low = Activity::factory()->for($subject)->create(['max_score' => 100, 'title' => 'Parcial flojo']);
        $high = Activity::factory()->for($subject)->create(['max_score' => 100, 'title' => 'Trabajo bueno']);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        Grade::factory()->create(['activity_id' => $low->id, 'student_id' => $student->id, 'score' => 40, 'graded_at' => now()->subDays(3)]);
        Grade::factory()->create(['activity_id' => $high->id, 'student_id' => $student->id, 'score' => 90, 'graded_at' => now()->subDay()]);

        return [$student, $course];
    }

    public function test_student_sees_own_analytics_page(): void
    {
        [$student] = $this->studentWithGrades();

        $this->actingAs($student)->get(route('analytics.student'))
            ->assertOk()
            ->assertSee('Mi analítica de aprendizaje')
            ->assertSee('Parcial flojo'); // actividad por debajo del umbral
    }

    public function test_report_flags_weak_activities_and_computes_distribution(): void
    {
        [$student] = $this->studentWithGrades();

        $report = app(PerformanceAnalyticsService::class)->studentReport($student);

        $this->assertSame(65.0, $report['overall_average']); // (40 + 90) / 2
        $this->assertCount(1, $report['weak_activities']);    // solo el de 40%
        $this->assertSame('Parcial flojo', $report['weak_activities']->first()['title']);
        $this->assertCount(2, $report['evolution']);

        $dist = collect($report['distribution'])->keyBy('label');
        $this->assertSame(1, $dist['0–59']['value']);
        $this->assertSame(1, $dist['90–100']['value']);
    }

    public function test_coordinator_cannot_open_student_analytics(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();

        $this->actingAs($coordinator)->get(route('analytics.student'))->assertForbidden();
    }
}
