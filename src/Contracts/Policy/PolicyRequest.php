<?php

declare(strict_types=1);

namespace Purple\Contracts\Policy;

final readonly class PolicyRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $operation,
        public string $provider,
        public string $model,
        public array $metadata = [],
    ) {
    }
}
