<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Domain\EnterpriseContext;

interface OrderWorkflowPort
{
    public function lookupOrder(string $orderId, EnterpriseContext $context): OrderSummary;
}
