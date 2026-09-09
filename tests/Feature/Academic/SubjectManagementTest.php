<?php

namespace Tests\Feature\Academic;

use App\Enums\AcademicStatus;
use App\Enums\RoleSlug;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_coordinator_creates_subject_and_assigns_teacher(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $course = Course::factory()->create();

        $this->actingAs($coordinator)
            ->post(route('coordinator.courses.subjects.store', $course), [
                'code' => 'MAT-101-A',
                'name' => 'Álgebra',
                'status' => AcademicStatus::Active->value,
                'teacher_id' => $teacher->id,
            ])
            ->assertRedirect(route('coordinator.courses.subjects.index', $course));

        $this->assertDatabaseHas('subjects', [
            'course_id' => $course->id,
            'code' => 'MAT-101-A',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_non_teacher_cannot_be_assigned_as_teacher(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        $course = Course::factory()->create();

        $this->actingAs($coordinator)
            ->post(route('coordinator.courses.subjects.store', $course), [
                'code' => 'X-1',
                'name' => 'X',
                'status' => AcademicStatus::Draft->value,
                'teacher_id' => $student->id,
            ])
            ->assertSessionHasErrors('teacher_id');
    }

    public function test_subject_position_autoincrements_within_course(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $course = Course::factory()->create();

        foreach (['A', 'B'] as $suffix) {
            $this->actingAs($coordinator)->post(route('coordinator.courses.subjects.store', $course), [
                'code' => "SUB-{$suffix}",
                'name' => "Sub {$suffix}",
                'status' => AcademicStatus::Active->value,
            ]);
        }

        $positions = $course->subjects()->orderBy('id')->pluck('position');
        $this->assertSame([1, 2], $positions->all());
    }
}
