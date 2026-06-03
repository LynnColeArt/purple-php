<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

final readonly class DraftRevision
{
    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public string $targetId,
        public string $summary,
        public array $changes = [],
    ) {
    }
}
