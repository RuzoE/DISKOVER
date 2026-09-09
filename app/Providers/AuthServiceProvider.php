<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\AiConversation;
use App\Models\Attempt;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\ImmersiveExperience;
use App\Models\Permission;
use App\Models\Recommendation;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\AiConversationPolicy;
use App\Policies\AttemptPolicy;
use App\Policies\ContentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\GradePolicy;
use App\Policies\ImmersiveExperiencePolicy;
use App\Policies\RecommendationPolicy;
use App\Policies\RolePolicy;
use App\Policies\SubjectPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapa modelo => policy.
     *
     * @var array<class-string, class-string>
     */
    private array $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Course::class => CoursePolicy::class,
        Subject::class => SubjectPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        Content::class => ContentPolicy::class,
        Activity::class => ActivityPolicy::class,
        Attempt::class => AttemptPolicy::class,
        Grade::class => GradePolicy::class,
        AiConversation::class => AiConversationPolicy::class,
        Recommendation::class => RecommendationPolicy::class,
        ImmersiveExperience::class => ImmersiveExperiencePolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        /*
         * El rol "super" (admin) supera cualquier comprobación de autorización.
         * Devolver null en el resto de casos deja que Laravel continúe con la
         * policy o el gate correspondiente. Ver ADR-0003.
         */
        Gate::before(
            fn (User $user, string $ability) => $user->hasRole(config('dsle.rbac.super_role')) ? true : null
        );

        $this->registerPermissionGates();
    }

    /**
     * Declara un Gate por cada permiso persistido, para poder usar
     * `@can('users.view')` en cualquier parte. La lista de slugs se cachea
     * para no consultar la base de datos en cada petición.
     */
    private function registerPermissionGates(): void
    {
        try {
            $slugs = cache()->remember(
                'dsle.permission-slugs',
                now()->addHour(),
                fn () => Schema::hasTable('permissions')
                    ? Permission::orderBy('slug')->pluck('slug')->all()
                    : [],
            );
        } catch (Throwable) {
            return; // Sin base de datos disponible (p. ej. durante el primer deploy).
        }

        foreach ($slugs as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
        }
    }
}
