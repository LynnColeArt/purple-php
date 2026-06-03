<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Approval\ApprovalDecision;
use Purple\Approval\ApprovalRequest;
use Purple\Domain\EnterpriseContext;

interface ApprovalWorkflowPort
{
    public function requestApproval(ApprovalRequest $request, EnterpriseContext $context): ApprovalDecision;
}
