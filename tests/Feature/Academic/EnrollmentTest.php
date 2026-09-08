<?php

namespace Tests\Feature\Academic;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleSlug;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function coordinator(): User
    {
        return User::factory()->withRole(RoleSlug::Coordinator->value)->create();
    }

    public function test_coordinator_can_enroll_a_student(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.courses.enrollments.store', $course), ['student_id' => $student->id])
            ->assertRedirect(route('coordinator.courses.enrollments.index', $course));

        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => EnrollmentStatus::Active->value,
        ]);
    }

    public function test_enrolling_twice_does_not_duplicate(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        foreach (range(1, 2) as $_) {
            $this->actingAs($this->coordinator())
                ->post(route('coordinator.courses.enrollments.store', $course), ['student_id' => $student->id]);
        }

        $this->assertSame(1, Enrollment::where('course_id', $course->id)->count());
    }

    public function test_cannot_enroll_a_non_student_user(): void
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.courses.enrollments.store', $course), ['student_id' => $teacher->id])
            ->assertSessionHasErrors('student_id');
    }

    public function test_coordinator_can_change_status_and_remove_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $coordinator = $this->coordinator();

        $this->actingAs($coordinator)
            ->put(route('coordinator.enrollments.update', $enrollment), [
                'status' => EnrollmentStatus::Completed->value,
            ])
            ->assertRedirect();

        $this->assertSame(EnrollmentStatus::Completed, $enrollment->fresh()->status);

        $this->actingAs($coordinator)
            ->delete(route('coordinator.enrollments.destroy', $enrollment))
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }
}
