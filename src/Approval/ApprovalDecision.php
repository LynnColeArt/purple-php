<?php

declare(strict_types=1);

namespace Purple\Approval;

final readonly class ApprovalDecision
{
    public function __construct(
        public bool $approved,
        public ?string $reason = null,
    ) {
    }

    public static function approve(?string $reason = null): self
    {
        return new self(true, $reason);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
