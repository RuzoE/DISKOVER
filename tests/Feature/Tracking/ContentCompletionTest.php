<?php

namespace Tests\Feature\Tracking;

use App\Enums\RoleSlug;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function scenario(): array
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $content = Content::factory()->for($subject)->create(['is_published' => true]);
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return [$content, $student];
    }

    public function test_student_toggles_content_completion(): void
    {
        [$content, $student] = $this->scenario();

        $this->actingAs($student)->post(route('student.contents.complete', $content))->assertRedirect();
        $this->assertDatabaseHas('content_user', ['content_id' => $content->id, 'user_id' => $student->id]);

        $this->actingAs($student)->post(route('student.contents.complete', $content))->assertRedirect();
        $this->assertDatabaseMissing('content_user', ['content_id' => $content->id, 'user_id' => $student->id]);
    }

    public function test_student_not_enrolled_cannot_mark_content(): void
    {
        [$content] = $this->scenario();
        $outsider = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($outsider)
            ->post(route('student.contents.complete', $content))
            ->assertForbidden();
    }
}
