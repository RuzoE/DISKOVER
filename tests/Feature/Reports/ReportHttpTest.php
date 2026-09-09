<?php

namespace Tests\Feature\Reports;

use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_coordinator_can_open_and_export_the_institutional_report(): void
    {
        $coordinator = User::factory()->withRole('coordinator')->create();

        $this->actingAs($coordinator)->get(route('reports.institutional'))
            ->assertOk()
            ->assertSee('Indicadores institucionales DSLE');

        $response = $this->actingAs($coordinator)->get(route('reports.institutional', ['export' => 'csv']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_coordinator_opens_a_course_report(): void
    {
        $coordinator = User::factory()->withRole('coordinator')->create();
        $course = Course::factory()->create();

        $this->actingAs($coordinator)->get(route('reports.course', $course))
            ->assertOk()
            ->assertSee('Reporte académico');
    }

    public function test_teacher_can_only_report_on_own_subjects(): void
    {
        $teacher = User::factory()->withRole('teacher')->create();
        $own = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $foreign = Subject::factory()->create();

        $this->actingAs($teacher)->get(route('reports.subject', $own))->assertOk();
        $this->actingAs($teacher)->get(route('reports.subject', $foreign))->assertForbidden();
    }

    public function test_teacher_cannot_open_course_or_institutional_reports(): void
    {
        $teacher = User::factory()->withRole('teacher')->create();
        $course = Course::factory()->create();

        $this->actingAs($teacher)->get(route('reports.course', $course))->assertForbidden();
        $this->actingAs($teacher)->get(route('reports.institutional'))->assertForbidden();
    }

    public function test_student_only_has_their_own_transcript(): void
    {
        $student = User::factory()->withRole('student')->create();
        $other = User::factory()->withRole('student')->create();

        $this->actingAs($student)->get(route('student.transcript'))
            ->assertOk()
            ->assertSee('Expediente académico');

        $this->actingAs($student)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($student)->get(route('reports.transcript', $other))->assertForbidden();
    }

    public function test_coordinator_can_open_any_student_transcript(): void
    {
        $coordinator = User::factory()->withRole('coordinator')->create();
        $student = User::factory()->withRole('student')->create();

        $this->actingAs($coordinator)->get(route('reports.transcript', $student))->assertOk();
    }
}
