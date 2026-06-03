<?php

declare(strict_types=1);

namespace Purple\Approval;

final readonly class StaticApprovalProvider implements ApprovalProvider
{
    public function __construct(
        private bool $approved,
        private ?string $reason = null,
    ) {
    }

    public function decide(ApprovalRequest $request): ApprovalDecision
    {
        if ($this->approved) {
            return ApprovalDecision::approve($this->reason);
        }

        return ApprovalDecision::deny($this->reason ?? 'Approval denied.');
    }
}
