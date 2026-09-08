<?php

namespace App\Services\Academic;

use App\Models\Course;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        return Course::create($this->normalize($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        $course->update($this->normalize($data));

        return $course;
    }

    public function delete(Course $course): void
    {
        DB::transaction(function () use ($course): void {
            // subjects, contents y enrollments caen por FK cascade.
            $course->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'code' => strtoupper(trim($data['code'])),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
        ];
    }
}
