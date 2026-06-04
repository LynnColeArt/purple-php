<?php

declare(strict_types=1);

namespace Purple\Policy\Rules;

use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Policy\PolicyRule;

final readonly class SideEffectApprovalRule implements PolicyRule
{
    /**
     * @param list<string> $levels
     */
    public function __construct(private array $levels = ['write', 'external'])
    {
    }

    public function evaluate(PolicyRequest $request): ?PolicyDecision
    {
        $sideEffectLevel = $request->metadata['side_effect_level'] ?? null;

        if (! is_string($sideEffectLevel) || ! in_array($sideEffectLevel, $this->levels, true)) {
            return null;
        }

        if (($request->metadata['approval_granted'] ?? false) === true) {
            return null;
        }

        return PolicyDecision::deny('Tool side effect requires explicit approval metadata.');
    }
}
