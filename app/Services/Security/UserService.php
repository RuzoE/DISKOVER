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

            $user->syncRoles(Arr::get($data, 'roles', []));

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
            $user->syncRoles(Arr::get($data, 'roles', []));

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->delete();
        });
    }
}
