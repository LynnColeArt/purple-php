<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Domain\EnterpriseContext;

interface SupportWorkflowPort
{
    public function classifyTicket(string $subject, string $body, EnterpriseContext $context): SupportClassification;
}
