<?php

namespace Tests\Feature\AI;

use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\AI\AcademicContextBuilder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_context_lists_courses_and_weak_subjects(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Marta']);
        $course = Course::factory()->create(['name' => 'Programación I']);
        $subject = Subject::factory()->for($course)->create(['name' => 'Bucles']);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 25]);

        $context = app(AcademicContextBuilder::class)->for($student);

        $this->assertStringContainsString('CONTEXTO ACADÉMICO', $context);
        $this->assertStringContainsString('Estudiante: Marta', $context);
        $this->assertStringContainsString('Programación I', $context);
        $this->assertStringContainsString('A REFORZAR: Bucles', $context);
    }

    public function test_non_student_gets_a_generic_context(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $context = app(AcademicContextBuilder::class)->for($teacher);

        $this->assertStringContainsString('no es estudiante', $context);
    }

    public function test_student_without_courses_is_reported(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->assertStringContainsString('no está inscrito', app(AcademicContextBuilder::class)->for($student));
    }
}
