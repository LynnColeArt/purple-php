<?php

declare(strict_types=1);

namespace Purple\Policy\Rules;

use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Policy\PolicyRule;

final readonly class RestrictedDataRouteRule implements PolicyRule
{
    /**
     * @param list<string> $allowedRoutes
     */
    public function __construct(private array $allowedRoutes)
    {
    }

    public function evaluate(PolicyRequest $request): ?PolicyDecision
    {
        if (($request->metadata['data_sensitivity'] ?? null) !== 'restricted') {
            return null;
        }

        $route = $request->metadata['provider_route'] ?? null;

        if (! is_string($route) || ! in_array($route, $this->allowedRoutes, true)) {
            return PolicyDecision::deny('Restricted data requires an approved provider route.');
        }

        return null;
    }
}
