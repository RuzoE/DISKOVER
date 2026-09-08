<?php

namespace Tests\Feature\Academic;

use App\Enums\AcademicStatus;
use App\Enums\RoleSlug;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function enrolledStudent(Course $course): User
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return $student;
    }

    public function test_student_sees_only_enrolled_courses(): void
    {
        $mine = Course::factory()->create(['name' => 'Mi curso']);
        $other = Course::factory()->create(['name' => 'Ajeno']);
        $student = $this->enrolledStudent($mine);

        $this->actingAs($student)
            ->get(route('student.courses.index'))
            ->assertOk()
            ->assertSee('Mi curso')
            ->assertDontSee('Ajeno');
    }

    public function test_student_cannot_open_a_course_they_are_not_enrolled_in(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)->get(route('student.courses.show', $course))->assertForbidden();
    }

    public function test_student_only_sees_published_content_of_active_subject(): void
    {
        $course = Course::factory()->create(['status' => AcademicStatus::Active->value]);
        $subject = Subject::factory()->create([
            'course_id' => $course->id,
            'status' => AcademicStatus::Active->value,
        ]);
        Content::factory()->for($subject)->create(['title' => 'Visible', 'is_published' => true]);
        Content::factory()->for($subject)->unpublished()->create(['title' => 'Oculto']);

        $student = $this->enrolledStudent($course);

        $this->actingAs($student)
            ->get(route('student.subjects.show', $subject))
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Oculto');
    }

    public function test_draft_subject_is_not_reachable_by_student(): void
    {
        $course = Course::factory()->create(['status' => AcademicStatus::Active->value]);
        $subject = Subject::factory()->draft()->create(['course_id' => $course->id]);
        $student = $this->enrolledStudent($course);

        $this->actingAs($student)->get(route('student.subjects.show', $subject))->assertNotFound();
    }

    public function test_teacher_area_is_forbidden_for_students(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)->get(route('teacher.subjects.index'))->assertForbidden();
    }
}
