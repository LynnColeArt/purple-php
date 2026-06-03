<?php

declare(strict_types=1);

namespace Purple\Approval;

interface ApprovalProvider
{
    public function decide(ApprovalRequest $request): ApprovalDecision;
}
