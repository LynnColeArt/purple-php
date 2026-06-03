<?php

declare(strict_types=1);

namespace Purple\Hooks;

final readonly class HookEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $type,
        public string $runId,
        public array $metadata = [],
    ) {
    }
}
