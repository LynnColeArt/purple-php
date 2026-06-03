<?php

declare(strict_types=1);

namespace Purple\Contracts\Policy;

interface PolicyEngine
{
    public function decide(PolicyRequest $request): PolicyDecision;
}
