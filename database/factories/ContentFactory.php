<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\Content;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'created_by' => null,
            'title' => fake()->sentence(4),
            'type' => ContentType::Text->value,
            'body' => fake()->paragraphs(2, true),
            'url' => null,
            'position' => fake()->numberBetween(0, 10),
            'is_published' => true,
        ];
    }

    public function link(): static
    {
        return $this->state(fn () => [
            'type' => ContentType::Link->value,
            'body' => null,
            'url' => fake()->url(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
