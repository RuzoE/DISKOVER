<?php

namespace App\DTOs\AI;

use App\Enums\IntegrityAction;

/**
 * Resultado de revisar un mensaje con AcademicIntegrityGuard.
 * `reason` es un código corto que se guarda en la auditoría (nunca el mensaje).
 */
final readonly class IntegrityVerdict
{
    public function __construct(
        public IntegrityAction $action,
        public ?string $reason = null,
        public ?string $reply = null,
    ) {}

    public static function allow(): self
    {
        return new self(IntegrityAction::Allow);
    }

    public static function guide(string $reason): self
    {
        return new self(IntegrityAction::Guide, $reason);
    }

    public static function block(string $reason, string $reply): self
    {
        return new self(IntegrityAction::Block, $reason, $reply);
    }

    public function isBlocked(): bool
    {
        return $this->action === IntegrityAction::Block;
    }

    public function isGuided(): bool
    {
        return $this->action === IntegrityAction::Guide;
    }
}
