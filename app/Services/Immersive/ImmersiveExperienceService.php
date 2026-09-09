<?php

namespace App\Services\Immersive;

use App\Models\ImmersiveExperience;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Alta y edición del catálogo de experiencias inmersivas y su vínculo con
 * asignaturas. Laravel sólo registra; no ejecuta la experiencia.
 */
class ImmersiveExperienceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $authorId): ImmersiveExperience
    {
        return DB::transaction(function () use ($data, $authorId): ImmersiveExperience {
            $experience = ImmersiveExperience::create($this->normalize($data) + ['created_by' => $authorId]);
            $experience->subjects()->sync($this->subjectPivot($data));

            return $experience;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ImmersiveExperience $experience, array $data): ImmersiveExperience
    {
        return DB::transaction(function () use ($experience, $data): ImmersiveExperience {
            $experience->update($this->normalize($data));
            $experience->subjects()->sync($this->subjectPivot($data));

            return $experience;
        });
    }

    public function delete(ImmersiveExperience $experience): void
    {
        // sessions y pivote caen por FK cascade.
        $experience->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'provider' => $data['provider'],
            'launch_url' => $data['launch_url'] ?? null,
            'config' => $this->decodeConfig($data['config'] ?? null),
            'max_score' => $data['max_score'] ?? 100,
            'status' => $data['status'],
            'activity_id' => $data['activity_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>> [subject_id => ['is_required' => bool]]
     */
    private function subjectPivot(array $data): array
    {
        $subjectIds = array_map('intval', (array) Arr::get($data, 'subject_ids', []));
        $required = array_map('intval', (array) Arr::get($data, 'required_subject_ids', []));

        $pivot = [];
        foreach ($subjectIds as $id) {
            $pivot[$id] = ['is_required' => in_array($id, $required, true)];
        }

        return $pivot;
    }

    private function decodeConfig(mixed $raw): ?array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
