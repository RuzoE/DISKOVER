<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $adminConfig = config('dsle.seed_admin');

        $admin = User::updateOrCreate(
            ['email' => $adminConfig['email']],
            [
                'name' => $adminConfig['name'],
                'password' => $adminConfig['password'],
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ],
        );

        $admin->assignRole(RoleSlug::Admin);

        // Datos de ejemplo solo fuera de producción.
        if (! app()->environment('production')) {
            User::factory()->withRole(RoleSlug::Coordinator->value)->create([
                'name' => 'Coordinación Demo',
                'email' => 'coordinacion@diskover.test',
            ]);

            User::factory()->withRole(RoleSlug::Teacher->value)->create([
                'name' => 'Docente Demo',
                'email' => 'docente@diskover.test',
            ]);

            User::factory()->withRole(RoleSlug::Student->value)->create([
                'name' => 'Estudiante Demo',
                'email' => 'estudiante@diskover.test',
            ]);
        }
    }
}
