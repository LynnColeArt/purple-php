<?php

declare(strict_types=1);

namespace Purple\Runtime;

final readonly class RuntimeMetrics
{
    public function __construct(
        public float $durationMs,
        public int $memoryDeltaBytes = 0,
    ) {
        if ($this->durationMs < 0.0) {
            throw new RuntimeException('Runtime duration must not be negative.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'duration_ms' => $this->durationMs,
            'memory_delta_bytes' => $this->memoryDeltaBytes,
        ];
    }
}
