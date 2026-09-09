<?php

namespace App\Policies;

use App\Models\Recommendation;
use App\Models\User;

class RecommendationPolicy
{
    public function update(User $user, Recommendation $recommendation): bool
    {
        return $recommendation->user_id === $user->id;
    }
}
