<?php

declare(strict_types=1);

namespace Purple\Runtime;

final readonly class NativeRuntimeCompatibilityReport
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public bool $compatible,
        public string $status,
        public string $operation,
        public array $payload = [],
        public ?RuntimeMetrics $metrics = null,
        public string $message = '',
    ) {
        if (trim($this->status) === '') {
            throw new RuntimeException('Native compatibility status must not be empty.');
        }

        if (trim($this->operation) === '') {
            throw new RuntimeException('Native compatibility operation must not be empty.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function compatible(string $operation, array $payload, RuntimeMetrics $metrics): self
    {
        return new self(
            compatible: true,
            status: 'compatible',
            operation: $operation,
            payload: $payload,
            metrics: $metrics,
            message: 'Native runtime satisfies the compatibility check.',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function incompatible(
        string $operation,
        string $message,
        array $payload = [],
        ?RuntimeMetrics $metrics = null,
    ): self {
        return new self(
            compatible: false,
            status: 'incompatible',
            operation: $operation,
            payload: $payload,
            metrics: $metrics,
            message: $message,
        );
    }

    public static function unavailable(string $operation, string $message): self
    {
        return new self(
            compatible: false,
            status: 'unavailable',
            operation: $operation,
            message: $message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'compatible' => $this->compatible,
            'status' => $this->status,
            'operation' => $this->operation,
            'payload' => $this->payload,
            'metrics' => $this->metrics?->toArray(),
            'message' => $this->message,
        ];
    }
}
