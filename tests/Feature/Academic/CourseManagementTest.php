<?php

namespace Tests\Feature\Academic;

use App\Enums\AcademicStatus;
use App\Enums\RoleSlug;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function coordinator(): User
    {
        return User::factory()->withRole(RoleSlug::Coordinator->value)->create();
    }

    public function test_coordinator_can_create_a_course(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('coordinator.courses.store'), [
                'code' => 'MAT-101',
                'name' => 'Matemática I',
                'description' => 'Curso base',
                'status' => AcademicStatus::Active->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', ['code' => 'MAT-101', 'name' => 'Matemática I']);
    }

    public function test_course_code_must_be_unique(): void
    {
        Course::factory()->create(['code' => 'DUP-1']);

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.courses.store'), [
                'code' => 'DUP-1',
                'name' => 'Otro',
                'status' => AcademicStatus::Draft->value,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_teacher_cannot_access_course_management(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($teacher)->get(route('coordinator.courses.index'))->assertForbidden();
    }

    public function test_student_cannot_access_course_management(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)->get(route('coordinator.courses.index'))->assertForbidden();
    }

    public function test_coordinator_can_update_and_delete_a_course(): void
    {
        $course = Course::factory()->create();
        $coordinator = $this->coordinator();

        $this->actingAs($coordinator)
            ->put(route('coordinator.courses.update', $course), [
                'code' => $course->code,
                'name' => 'Nombre nuevo',
                'status' => AcademicStatus::Archived->value,
            ])
            ->assertRedirect();

        $this->assertSame('Nombre nuevo', $course->fresh()->name);

        $this->actingAs($coordinator)
            ->delete(route('coordinator.courses.destroy', $course))
            ->assertRedirect(route('coordinator.courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }
}
