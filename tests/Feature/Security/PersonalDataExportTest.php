<?php

namespace Tests\Feature\Security;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Services\Reports\PersonalDataReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class PersonalDataExportTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_student_can_download_their_own_data_as_csv(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Grace Hopper']);

        $response = $this->actingAs($student)->get(route('student.data.download'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_report_contains_account_and_activity_sections(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Grace Hopper']);

        $report = new PersonalDataReport($student);
        $flat = collect($report->rows());

        $this->assertTrue($flat->contains(fn ($row) => $row[0] === 'Cuenta' && $row[1] === 'Correo' && $row[2] === $student->email));
        $this->assertTrue($flat->contains(fn ($row) => $row[0] === 'Actividad'));
        $this->assertSame('mis-datos-'.$student->id, $report->key());
    }

    public function test_guests_cannot_download_personal_data(): void
    {
        $this->get(route('student.data.download'))->assertRedirect(route('login'));
    }
}
