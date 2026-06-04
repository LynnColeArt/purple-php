<?php

declare(strict_types=1);

namespace Purple\Policy\Rules;

use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Policy\PolicyRule;

final readonly class RetentionLimitRule implements PolicyRule
{
    public function __construct(private int $maxRetentionDays)
    {
    }

    public function evaluate(PolicyRequest $request): ?PolicyDecision
    {
        $retentionDays = $request->metadata['retention_days'] ?? null;

        if (! is_int($retentionDays) || $retentionDays <= $this->maxRetentionDays) {
            return null;
        }

        return PolicyDecision::deny('Retention period exceeds policy maximum.');
    }
}
