<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'.'.fake()->randomElement(['view', 'create', 'update', 'delete']),
            'group' => fake()->randomElement(['users', 'roles', 'academic', 'reports']),
            'description' => fake()->sentence(),
        ];
    }
}
