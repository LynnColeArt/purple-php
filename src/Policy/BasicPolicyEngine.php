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
     * @param list<string> $allowedTenantIds
     * @param list<string> $allowedProviderRoutes
     * @param list<string> $allowedDataResidencyRegions
     */
    public function __construct(
        private readonly array $allowedProviders = [],
        private readonly array $allowedModels = [],
        private readonly ?int $maxRuns = null,
        private readonly ?float $maxEstimatedCostUsd = null,
        private readonly array $allowedTenantIds = [],
        private readonly array $allowedProviderRoutes = [],
        private readonly array $allowedDataResidencyRegions = [],
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

        if (! $this->metadataValueAllowed($request->metadata['tenant_id'] ?? null, $this->allowedTenantIds)) {
            return PolicyDecision::deny('Tenant is not allowed by policy.');
        }

        if (! $this->metadataValueAllowed($request->metadata['provider_route'] ?? null, $this->allowedProviderRoutes)) {
            return PolicyDecision::deny('Provider route is not allowed by policy.');
        }

        if (! $this->metadataValueAllowed($request->metadata['data_residency_region'] ?? null, $this->allowedDataResidencyRegions)) {
            return PolicyDecision::deny('Data residency region is not allowed by policy.');
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

    /**
     * @param list<string> $allowedValues
     */
    private function metadataValueAllowed(mixed $value, array $allowedValues): bool
    {
        if ($allowedValues === []) {
            return true;
        }

        return is_string($value) && in_array($value, $allowedValues, true);
    }
}
