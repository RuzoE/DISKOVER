<?php

namespace Tests\Feature\Tracking;

use App\Enums\RoleSlug;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_can_see_own_profile_and_course_progress(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->actingAs($student)->get(route('student.profile.show'))->assertOk();
        $this->actingAs($student)->get(route('student.courses.progress', $course))->assertOk();
    }

    public function test_student_cannot_see_progress_of_a_course_they_are_not_in(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)
            ->get(route('student.courses.progress', $course))
            ->assertForbidden();
    }

    public function test_teacher_sees_progress_of_own_subject_only(): void
    {
        $owner = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $subject = Subject::factory()->create(['teacher_id' => $owner->id]);
        $intruder = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($owner)->get(route('teacher.subjects.progress', $subject))->assertOk();
        $this->actingAs($intruder)->get(route('teacher.subjects.progress', $subject))->assertForbidden();
    }

    public function test_coordinator_sees_course_progress(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $course = Course::factory()->create();

        $this->actingAs($coordinator)->get(route('coordinator.courses.progress', $course))->assertOk();
    }

    public function test_student_cannot_reach_coordinator_progress(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        $course = Course::factory()->create();

        $this->actingAs($student)
            ->get(route('coordinator.courses.progress', $course))
            ->assertForbidden();
    }
}
