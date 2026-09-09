<?php

namespace Tests\Feature\Assessment;

use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class StudentActivityAccessTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function enrolledStudent(Course $course): User
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return $student;
    }

    public function test_enrolled_student_can_open_a_published_activity(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $activity = Activity::factory()->for($subject)->create(['is_published' => true, 'title' => 'Trabajo visible']);

        $this->actingAs($this->enrolledStudent($course))
            ->get(route('student.activities.show', $activity))
            ->assertOk()
            ->assertSee('Trabajo visible');
    }

    public function test_unpublished_activity_is_not_found_for_student(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $activity = Activity::factory()->for($subject)->unpublished()->create();

        $this->actingAs($this->enrolledStudent($course))
            ->get(route('student.activities.show', $activity))
            ->assertNotFound();
    }

    public function test_student_cannot_open_activity_of_a_course_they_are_not_enrolled_in(): void
    {
        $subject = Subject::factory()->create();
        $activity = Activity::factory()->for($subject)->create(['is_published' => true]);
        $outsider = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($outsider)
            ->get(route('student.activities.show', $activity))
            ->assertForbidden();
    }
}
