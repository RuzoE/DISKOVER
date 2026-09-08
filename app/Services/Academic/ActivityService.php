<?php

namespace App\Services\Academic;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Subject $subject, array $data): Activity
    {
        return DB::transaction(function () use ($subject, $data): Activity {
            $position = (int) $subject->activities()->max('position') + 1;

            $activity = $subject->activities()->create([
                'type' => $data['type'],
                'position' => $data['position'] ?? $position,
                ...$this->editableAttributes($data),
            ]);

            if ($activity->type === ActivityType::Quiz) {
                $activity->evaluation()->create([]);
            }

            return $activity;
        });
    }

    /**
     * El tipo de actividad es inmutable tras la creación.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Activity $activity, array $data): Activity
    {
        $activity->update($this->editableAttributes($data));

        return $activity;
    }

    public function delete(Activity $activity): void
    {
        // evaluation, questions, attempts y grades caen por FK cascade.
        $activity->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function editableAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'max_score' => $data['max_score'] ?? 100,
            'opens_at' => $data['opens_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ];
    }
}
