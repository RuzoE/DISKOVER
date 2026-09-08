<?php

namespace App\Services\Academic;

use App\Enums\ContentType;
use App\Models\Content;
use App\Models\Subject;
use App\Models\User;

class ContentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Subject $subject, User $author, array $data): Content
    {
        return $subject->contents()->create(
            $this->normalize($data, $subject) + ['created_by' => $author->id]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Content $content, array $data): Content
    {
        $content->update($this->normalize($data, $content->subject));

        return $content;
    }

    public function delete(Content $content): void
    {
        $content->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, Subject $subject): array
    {
        $type = $data['type'] instanceof ContentType ? $data['type'] : ContentType::from($data['type']);

        $position = $data['position']
            ?? ((int) $subject->contents()->max('position') + 1);

        return [
            'title' => $data['title'],
            'type' => $type,
            'body' => $type === ContentType::Text ? ($data['body'] ?? null) : null,
            'url' => $type->requiresUrl() ? ($data['url'] ?? null) : null,
            'position' => $position,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ];
    }
}
