<?php

declare(strict_types=1);

namespace Purple\Contracts\Schema;

final readonly class ValidationResult
{
    /**
     * @param list<string> $violations
     */
    private function __construct(
        public bool $valid,
        public array $violations = [],
    ) {
    }

    public static function pass(): self
    {
        return new self(true);
    }

    /**
     * @param list<string> $violations
     */
    public static function fail(array $violations): self
    {
        return new self(false, $violations);
    }
}
