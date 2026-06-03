<?php

declare(strict_types=1);

namespace Purple\Agent;

use Purple\Approval\ApprovalRequest;

final readonly class AgentRunResult
{
    public function __construct(
        public AgentRunStatus $status,
        public string $runId,
        public int $steps = 0,
        public int $toolCalls = 0,
        public ?string $answer = null,
        public ?string $reason = null,
        public ?ApprovalRequest $approvalRequest = null,
    ) {
    }
}
