<?php

declare(strict_types=1);

namespace Purple\Policy;

use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Policy\PolicyRule;

final readonly class EnterprisePolicyEngine implements PolicyEngine
{
    /**
     * @param list<PolicyRule> $rules
     */
    public function __construct(
        private PolicyEngine $basePolicy,
        private array $rules = [],
    ) {
    }

    public function decide(PolicyRequest $request): PolicyDecision
    {
        $baseDecision = $this->basePolicy->decide($request);

        if (! $baseDecision->allowed) {
            return $baseDecision;
        }

        foreach ($this->rules as $rule) {
            $decision = $rule->evaluate($request);

            if ($decision !== null && ! $decision->allowed) {
                return $decision;
            }
        }

        return PolicyDecision::allow('Enterprise policy checks passed.');
    }
}
