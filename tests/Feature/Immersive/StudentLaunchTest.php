<?php

namespace Tests\Feature\Immersive;

use App\Enums\ImmersiveSessionStatus;
use App\Enums\RoleSlug;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class StudentLaunchTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function scenario(): array
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $experience = ImmersiveExperience::factory()->create();
        $experience->subjects()->attach($subject->id, ['is_required' => false]);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        return [$experience, $student];
    }

    public function test_enrolled_student_launches_and_gets_a_session(): void
    {
        [$experience, $student] = $this->scenario();

        $this->actingAs($student)
            ->post(route('student.immersive.launch', $experience))
            ->assertRedirect();

        $session = $student->immersiveSessions()->firstOrFail();
        $this->assertSame(ImmersiveSessionStatus::Started, $session->status);
        $this->assertSame(64, strlen($session->launch_token));
    }

    public function test_relaunch_resumes_the_open_session(): void
    {
        [$experience, $student] = $this->scenario();

        $this->actingAs($student)->post(route('student.immersive.launch', $experience));
        $this->actingAs($student)->post(route('student.immersive.launch', $experience));

        $this->assertSame(1, $student->immersiveSessions()->count());
    }

    public function test_student_not_in_any_linked_subject_cannot_launch(): void
    {
        $experience = ImmersiveExperience::factory()->create();
        $experience->subjects()->attach(Subject::factory()->create()->id);
        $outsider = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($outsider)
            ->post(route('student.immersive.launch', $experience))
            ->assertForbidden();
    }

    public function test_student_cannot_view_another_students_session(): void
    {
        $session = ImmersiveSession::factory()->create();
        $other = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($other)->get(route('student.immersive.session', $session))->assertForbidden();
    }

    public function test_draft_experience_is_not_launchable(): void
    {
        [$experience, $student] = $this->scenario();
        $experience->update(['status' => 'draft']);

        $this->actingAs($student)
            ->post(route('student.immersive.launch', $experience))
            ->assertForbidden();
    }
}
