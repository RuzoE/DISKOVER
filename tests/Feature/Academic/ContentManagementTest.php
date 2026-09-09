<?php

namespace Tests\Feature\Academic;

use App\Enums\ContentType;
use App\Enums\RoleSlug;
use App\Models\Content;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->withRole(RoleSlug::Teacher->value)->create();
    }

    public function test_teacher_can_add_text_content_to_own_subject(): void
    {
        $teacher = $this->teacher();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.subjects.contents.store', $subject), [
                'title' => 'Tema 1',
                'type' => ContentType::Text->value,
                'body' => 'Contenido del tema',
                'is_published' => '1',
            ])
            ->assertRedirect(route('teacher.subjects.contents.index', $subject));

        $this->assertDatabaseHas('contents', [
            'subject_id' => $subject->id,
            'title' => 'Tema 1',
            'created_by' => $teacher->id,
            'is_published' => true,
        ]);
    }

    public function test_link_content_requires_url(): void
    {
        $teacher = $this->teacher();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.subjects.contents.store', $subject), [
                'title' => 'Enlace',
                'type' => ContentType::Link->value,
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_teacher_cannot_manage_content_of_another_teachers_subject(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $subject = Subject::factory()->create(['teacher_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('teacher.subjects.contents.create', $subject))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('teacher.subjects.contents.store', $subject), [
                'title' => 'X', 'type' => ContentType::Text->value, 'body' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_update_and_delete_own_content(): void
    {
        $teacher = $this->teacher();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $content = Content::factory()->for($subject)->create();

        $this->actingAs($teacher)
            ->put(route('teacher.contents.update', $content), [
                'title' => 'Actualizado',
                'type' => ContentType::Text->value,
                'body' => 'nuevo cuerpo',
            ])
            ->assertRedirect();

        $this->assertSame('Actualizado', $content->fresh()->title);

        $this->actingAs($teacher)
            ->delete(route('teacher.contents.destroy', $content))
            ->assertRedirect();

        $this->assertDatabaseMissing('contents', ['id' => $content->id]);
    }
}
