<?php

declare(strict_types=1);

namespace Purple\Policy;

use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;

final class BasicPolicyEngine implements PolicyEngine
{
    private int $runs = 0;

    /**
     * @param list<string> $allowedProviders
     * @param list<string> $allowedModels
     */
    public function __construct(
        private readonly array $allowedProviders = [],
        private readonly array $allowedModels = [],
        private readonly ?int $maxRuns = null,
        private readonly ?float $maxEstimatedCostUsd = null,
    ) {
    }

    public function decide(PolicyRequest $request): PolicyDecision
    {
        if ($this->allowedProviders !== [] && ! in_array($request->provider, $this->allowedProviders, true)) {
            return PolicyDecision::deny(sprintf('Provider "%s" is not allowed.', $request->provider));
        }

        if ($this->allowedModels !== [] && ! in_array($request->model, $this->allowedModels, true)) {
            return PolicyDecision::deny(sprintf('Model "%s" is not allowed.', $request->model));
        }

        if ($this->maxRuns !== null && $this->runs >= $this->maxRuns) {
            return PolicyDecision::deny('Run budget has been exhausted.');
        }

        $estimatedCost = $request->metadata['estimated_cost_usd'] ?? null;

        if (
            $this->maxEstimatedCostUsd !== null
            && is_numeric($estimatedCost)
            && (float) $estimatedCost > $this->maxEstimatedCostUsd
        ) {
            return PolicyDecision::deny('Estimated cost exceeds policy budget.');
        }

        $this->runs++;

        return PolicyDecision::allow('Policy checks passed.');
    }
}
