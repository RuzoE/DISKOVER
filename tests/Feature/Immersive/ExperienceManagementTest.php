<?php

namespace Tests\Feature\Immersive;

use App\Enums\ImmersiveProvider;
use App\Enums\RoleSlug;
use App\Models\ImmersiveExperience;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperienceManagementTest extends TestCase
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

    public function test_coordinator_registers_an_experience_linked_to_subjects(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs($this->coordinator())
            ->post(route('immersive.experiences.store'), [
                'title' => 'Recorrido por el laboratorio',
                'provider' => ImmersiveProvider::Simulator->value,
                'max_score' => 100,
                'status' => 'active',
                'subject_ids' => [$subject->id],
                'required_subject_ids' => [$subject->id],
            ])
            ->assertRedirect();

        $experience = ImmersiveExperience::firstOrFail();
        $this->assertNotEmpty($experience->slug);
        $this->assertTrue($experience->subjects()->where('subjects.id', $subject->id)->wherePivot('is_required', true)->exists());
    }

    public function test_launch_url_is_required_for_non_simulator_providers(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('immersive.experiences.store'), [
                'title' => 'X',
                'provider' => ImmersiveProvider::UnityWebgl->value,
                'max_score' => 100,
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('launch_url');
    }

    public function test_config_must_be_valid_json(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('immersive.experiences.store'), [
                'title' => 'X',
                'provider' => ImmersiveProvider::Simulator->value,
                'config' => 'no soy json {',
                'max_score' => 100,
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('config');
    }

    public function test_teacher_and_student_cannot_manage_experiences(): void
    {
        foreach ([RoleSlug::Teacher, RoleSlug::Student] as $role) {
            $user = User::factory()->withRole($role->value)->create();
            $this->actingAs($user)->get(route('immersive.experiences.index'))->assertForbidden();
        }
    }

    public function test_coordinator_can_update_and_delete(): void
    {
        $experience = ImmersiveExperience::factory()->create();
        $coordinator = $this->coordinator();

        $this->actingAs($coordinator)
            ->put(route('immersive.experiences.update', $experience), [
                'title' => 'Nombre nuevo',
                'slug' => $experience->slug,
                'provider' => ImmersiveProvider::Simulator->value,
                'max_score' => 50,
                'status' => 'archived',
            ])
            ->assertRedirect();

        $this->assertSame('Nombre nuevo', $experience->fresh()->title);

        $this->actingAs($coordinator)
            ->delete(route('immersive.experiences.destroy', $experience))
            ->assertRedirect(route('immersive.experiences.index'));

        $this->assertDatabaseMissing('immersive_experiences', ['id' => $experience->id]);
    }
}
