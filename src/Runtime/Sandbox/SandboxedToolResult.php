<?php

declare(strict_types=1);

namespace Purple\Runtime\Sandbox;

use Purple\Runtime\RuntimeMetrics;

final readonly class SandboxedToolResult
{
    public function __construct(
        public mixed $output,
        public RuntimeMetrics $metrics,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'output' => $this->output,
            'metrics' => $this->metrics->toArray(),
        ];
    }
}
