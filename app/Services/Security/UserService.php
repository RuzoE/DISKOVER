<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Punto de entrada del módulo de seguridad para la gestión de usuarios.
 * Los controladores no manipulan el modelo User directamente.
 */
class UserService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  Datos ya validados por el Form Request.
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => $data['status'],
            ]);

            $roles = Arr::get($data, 'roles', []);
            $user->syncRoles($roles);
            $this->syncDirectPermissions($user, $data);

            $this->audit->record('user.roles_assigned', $user, ['roles' => $roles]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Datos ya validados por el Form Request.
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['status'],
            ];

            if (filled(Arr::get($data, 'password'))) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);

            $oldRoles = $user->roles->pluck('slug')->sort()->values()->all();
            $newRoles = collect(Arr::get($data, 'roles', []))->sort()->values()->all();
            $user->syncRoles($newRoles);

            if ($oldRoles !== $newRoles) {
                $this->audit->record('user.roles_changed', $user, ['old' => $oldRoles, 'new' => $newRoles]);
            }

            $this->syncDirectPermissions($user, $data);

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->directPermissions()->detach();
            $user->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncDirectPermissions(User $user, array $data): void
    {
        if (! array_key_exists('direct_permissions', $data)) {
            return;
        }

        $old = $user->directPermissions->pluck('slug')->sort()->values()->all();
        $new = collect($data['direct_permissions'] ?? [])->map('strval')->sort()->values()->all();

        $user->syncDirectPermissions($new);

        if ($old !== $new) {
            $this->audit->record('user.permissions_changed', $user, ['old' => $old, 'new' => $new]);
        }
    }
}
