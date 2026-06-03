<?php

declare(strict_types=1);

namespace Purple\Contracts\Policy;

use InvalidArgumentException;

final readonly class PolicyDecision
{
    private function __construct(
        public bool $allowed,
        public ?string $reason = null,
    ) {
        if (! $this->allowed && ($this->reason === null || trim($this->reason) === '')) {
            throw new InvalidArgumentException('Denied policy decisions must include a reason.');
        }
    }

    public static function allow(?string $reason = null): self
    {
        return new self(true, $reason);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
