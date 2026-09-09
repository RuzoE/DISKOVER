<?php

namespace App\DTOs\Recommendations;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;

/**
 * Recomendación candidata producida por una regla, antes de persistirse.
 */
final readonly class RecommendationDraft
{
    /**
     * @param  array<string, mixed>  $reason
     */
    public function __construct(
        public RecommendationType $type,
        public RecommendationPriority $priority,
        public string $title,
        public string $body,
        public string $signature,
        public array $reason = [],
        public ?int $subjectId = null,
        public ?int $activityId = null,
        public ?int $contentId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'title' => $this->title,
            'body' => $this->body,
            'reason' => $this->reason,
            'subject_id' => $this->subjectId,
            'activity_id' => $this->activityId,
            'content_id' => $this->contentId,
        ];
    }
}
