<?php

declare(strict_types=1);

namespace Purple\Contracts\Provider;

final readonly class ProviderResponse
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $content,
        public array $metadata = [],
        public ?ProviderUsage $usage = null,
    ) {
    }
}
