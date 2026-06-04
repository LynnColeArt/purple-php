<?php

declare(strict_types=1);

namespace Purple\Runtime;

final readonly class NativeRuntimeResult
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $operation,
        public string $status,
        public array $payload = [],
        public ?RuntimeMetrics $metrics = null,
    ) {
        if (trim($this->operation) === '') {
            throw new RuntimeException('Native runtime operation must not be empty.');
        }

        if (trim($this->status) === '') {
            throw new RuntimeException('Native runtime status must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'status' => $this->status,
            'payload' => $this->payload,
            'metrics' => $this->metrics?->toArray(),
        ];
    }
}
