<?php

namespace App\Events\Academic;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ContentCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly Content $content,
        public readonly User $student,
    ) {}
}
