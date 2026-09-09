<?php

namespace App\DTOs\AI;

use App\Enums\AiMessageRole;

/**
 * Un mensaje del hilo que se envía al proveedor de IA.
 */
final readonly class ChatMessage
{
    public function __construct(
        public AiMessageRole $role,
        public string $content,
    ) {}

    public static function system(string $content): self
    {
        return new self(AiMessageRole::System, $content);
    }

    public static function user(string $content): self
    {
        return new self(AiMessageRole::User, $content);
    }

    public static function assistant(string $content): self
    {
        return new self(AiMessageRole::Assistant, $content);
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return ['role' => $this->role->value, 'content' => $this->content];
    }
}
