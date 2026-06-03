<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

final readonly class OrderSummary
{
    public function __construct(
        public string $orderId,
        public string $customerLabel,
        public string $status,
        public float $total,
    ) {
    }
}
