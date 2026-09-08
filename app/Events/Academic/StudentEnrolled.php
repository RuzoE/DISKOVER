<?php

namespace App\Events\Academic;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;

class StudentEnrolled
{
    use Dispatchable;

    public function __construct(public readonly Enrollment $enrollment) {}
}
