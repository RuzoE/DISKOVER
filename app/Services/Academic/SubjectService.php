<?php

namespace App\Services\Academic;

use App\Models\Course;
use App\Models\Subject;

class SubjectService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Course $course, array $data): Subject
    {
        return $course->subjects()->create($this->normalize($data, $course));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($this->normalize($data, $subject->course));

        return $subject;
    }

    public function delete(Subject $subject): void
    {
        // contents caen por FK cascade.
        $subject->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, Course $course): array
    {
        $position = $data['position']
            ?? ((int) $course->subjects()->max('position') + 1);

        return [
            'teacher_id' => $data['teacher_id'] ?? null,
            'code' => strtoupper(trim($data['code'])),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $position,
            'status' => $data['status'],
        ];
    }
}
