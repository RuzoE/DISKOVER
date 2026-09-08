<?php

namespace App\Events\Academic;

use App\Models\Grade;
use Illuminate\Foundation\Events\Dispatchable;

class ActivityGraded
{
    use Dispatchable;

    public function __construct(public readonly Grade $grade) {}
}
