<?php

declare(strict_types=1);

namespace Purple\Runtime\Sandbox;

use Purple\Runtime\RuntimeException;
use Purple\Tool\ToolSideEffectLevel;

final readonly class SandboxPolicy
{
    /**
     * @param list<ToolSideEffectLevel> $allowedSideEffects
     */
    public function __construct(
        public array $allowedSideEffects = [ToolSideEffectLevel::None, ToolSideEffectLevel::Read],
        public int $maxPayloadBytes = 65536,
        public int $maxDurationMs = 1000,
    ) {
        if ($this->allowedSideEffects === []) {
            throw new RuntimeException('Sandbox policy must allow at least one side-effect level.');
        }

        if ($this->maxPayloadBytes < 1) {
            throw new RuntimeException('Sandbox max payload bytes must be positive.');
        }

        if ($this->maxDurationMs < 1) {
            throw new RuntimeException('Sandbox max duration must be positive.');
        }
    }

    public function assertSideEffectAllowed(ToolSideEffectLevel $level): void
    {
        foreach ($this->allowedSideEffects as $allowed) {
            if ($allowed === $level) {
                return;
            }
        }

        throw new RuntimeException(sprintf('Tool side-effect level "%s" is not allowed by sandbox policy.', $level->value));
    }

    public function assertPayloadAllowed(string $label, int $bytes): void
    {
        if ($bytes > $this->maxPayloadBytes) {
            throw new RuntimeException(sprintf('%s exceeds sandbox payload limit of %d bytes.', $label, $this->maxPayloadBytes));
        }
    }

    public function assertDurationAllowed(float $durationMs): void
    {
        if ($durationMs > $this->maxDurationMs) {
            throw new RuntimeException(sprintf('Tool execution exceeded sandbox duration limit of %d ms.', $this->maxDurationMs));
        }
    }
}
