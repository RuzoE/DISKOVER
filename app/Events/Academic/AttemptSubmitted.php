<?php

namespace App\Events\Academic;

use App\Models\Attempt;
use Illuminate\Foundation\Events\Dispatchable;

class AttemptSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Attempt $attempt) {}
}
