<?php

declare(strict_types=1);

namespace Purple\Contracts\Provider;

use InvalidArgumentException;

final readonly class ProviderUsage
{
    public function __construct(
        public int $inputTokens,
        public int $outputTokens,
        public ?float $costUsd = null,
    ) {
        if ($this->inputTokens < 0 || $this->outputTokens < 0) {
            throw new InvalidArgumentException('Token counts must not be negative.');
        }

        if ($this->costUsd !== null && $this->costUsd < 0.0) {
            throw new InvalidArgumentException('Cost must not be negative.');
        }
    }

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
