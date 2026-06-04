<?php

declare(strict_types=1);

namespace Purple\Contracts\Policy;

interface PolicyRule
{
    public function evaluate(PolicyRequest $request): ?PolicyDecision;
}
