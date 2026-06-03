<?php

declare(strict_types=1);

namespace Purple\Approval;

final readonly class ApprovalRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $id,
        public string $runId,
        public string $toolName,
        public string $reason,
        public array $metadata = [],
    ) {
    }
}
