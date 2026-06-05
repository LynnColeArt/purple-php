<?php

declare(strict_types=1);

namespace Purple\Runtime;

use Purple\Contracts\Runtime\NativeRuntime;
use Throwable;

final readonly class NativeRuntimeCompatibility
{
    public const OPERATION = 'runtime.acceptance.ping';

    public const EXPECTED_ANSWER = 'native-compatible';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $operation = self::OPERATION,
        private array $payload = ['tenant_id' => 'compatibility-fixture'],
    ) {
        if (trim($this->operation) === '') {
            throw new RuntimeException('Native compatibility operation must not be empty.');
        }
    }

    /**
     * @return callable(string, array<string, mixed>): array<string, mixed>
     */
    public static function compatibleFixtureInvoker(): callable
    {
        return static fn (string $operation, array $payload): array => [
            'operation' => $operation,
            'status' => 'ok',
            'payload' => [
                'answer' => ($payload['tenant_id'] ?? null) === null ? 'missing-tenant' : self::EXPECTED_ANSWER,
            ],
            'metrics' => [
                'duration_ms' => 1.0,
                'memory_delta_bytes' => 0,
            ],
        ];
    }

    public function check(NativeRuntime $runtime): NativeRuntimeCompatibilityReport
    {
        try {
            $result = $runtime->invoke($this->operation, $this->payload);
        } catch (Throwable $exception) {
            return $this->reportException($exception);
        }

        if ($result->operation !== $this->operation) {
            return NativeRuntimeCompatibilityReport::incompatible(
                operation: $this->operation,
                message: sprintf('Expected native operation "%s", got "%s".', $this->operation, $result->operation),
                payload: $result->payload,
                metrics: $result->metrics,
            );
        }

        if ($result->status !== 'ok') {
            return NativeRuntimeCompatibilityReport::incompatible(
                operation: $this->operation,
                message: sprintf('Expected native status "ok", got "%s".', $result->status),
                payload: $result->payload,
                metrics: $result->metrics,
            );
        }

        if (($result->payload['answer'] ?? null) !== self::EXPECTED_ANSWER) {
            return NativeRuntimeCompatibilityReport::incompatible(
                operation: $this->operation,
                message: 'Expected native payload answer "' . self::EXPECTED_ANSWER . '".',
                payload: $result->payload,
                metrics: $result->metrics,
            );
        }

        if ($result->metrics === null) {
            return NativeRuntimeCompatibilityReport::incompatible(
                operation: $this->operation,
                message: 'Expected native runtime metrics.',
                payload: $result->payload,
            );
        }

        return NativeRuntimeCompatibilityReport::compatible(
            operation: $this->operation,
            payload: $result->payload,
            metrics: $result->metrics,
        );
    }

    private function reportException(Throwable $exception): NativeRuntimeCompatibilityReport
    {
        $message = $exception->getMessage();

        if ($this->isUnavailableMessage($message)) {
            return NativeRuntimeCompatibilityReport::unavailable(
                operation: $this->operation,
                message: $message,
            );
        }

        return NativeRuntimeCompatibilityReport::incompatible(
            operation: $this->operation,
            message: $message,
        );
    }

    private function isUnavailableMessage(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'not available')
            || str_contains($normalized, 'not loaded')
            || str_contains($normalized, 'missing extension');
    }
}
