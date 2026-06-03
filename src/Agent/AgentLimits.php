<?php

declare(strict_types=1);

namespace Purple\Agent;

final readonly class AgentLimits
{
    public function __construct(
        public int $maxSteps = 8,
        public int $maxToolCalls = 8,
        public int $maxProviderRetries = 1,
        public int $maxSeconds = 30,
    ) {
        if ($this->maxSteps < 1) {
            throw new AgentException('Agent max steps must be at least 1.');
        }

        if ($this->maxToolCalls < 0 || $this->maxProviderRetries < 0 || $this->maxSeconds < 1) {
            throw new AgentException('Agent limits must not be negative.');
        }
    }
}
