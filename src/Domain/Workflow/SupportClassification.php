<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

final readonly class SupportClassification
{
    public function __construct(
        public string $priority,
        public string $category,
        public string $reason,
    ) {
    }
}
