<?php

declare(strict_types=1);

namespace Purple\Runtime;

use Purple\Contracts\Runtime\NativeRuntime;
use Throwable;

final readonly class PhpExtensionBridge implements NativeRuntime
{
    /** @var null|callable(string, array<string, mixed>): array<string, mixed> */
    private mixed $invoker;

    /**
     * @param null|callable(string, array<string, mixed>): array<string, mixed> $invoker
     */
    public function __construct(
        private string $extensionName = 'purple_native',
        ?callable $invoker = null,
    ) {
        $this->invoker = $invoker;
    }

    public function available(): bool
    {
        return $this->invoker !== null
            || extension_loaded($this->extensionName)
            || function_exists('purple_native_invoke');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function invoke(string $operation, array $payload): NativeRuntimeResult
    {
        if (trim($operation) === '') {
            throw new RuntimeException('Native runtime operation must not be empty.');
        }

        $startedAt = microtime(true);
        $memoryBefore = memory_get_usage();

        try {
            $response = $this->callInvoker($operation, $payload);
        } catch (Throwable $exception) {
            throw new RuntimeException('Native runtime invocation failed: ' . $exception->getMessage(), 0, $exception);
        }

        $metrics = new RuntimeMetrics(
            durationMs: (microtime(true) - $startedAt) * 1000.0,
            memoryDeltaBytes: memory_get_usage() - $memoryBefore,
        );

        return $this->resultFromResponse($operation, $response, $metrics);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function callInvoker(string $operation, array $payload): array
    {
        if ($this->invoker !== null) {
            return ($this->invoker)($operation, $payload);
        }

        if (function_exists('purple_native_invoke')) {
            $response = purple_native_invoke($operation, $payload);

            if (! is_array($response) || ($response !== [] && array_is_list($response))) {
                throw new RuntimeException('Native runtime response must be an associative array.');
            }

            return $this->stringKeyedArray($response, 'Native runtime response');
        }

        throw new RuntimeException(sprintf('Purple native runtime extension "%s" is not available.', $this->extensionName));
    }

    /**
     * @param array<string, mixed> $response
     */
    private function resultFromResponse(string $operation, array $response, RuntimeMetrics $fallbackMetrics): NativeRuntimeResult
    {
        $status = $response['status'] ?? 'ok';

        if (! is_string($status) || trim($status) === '') {
            throw new RuntimeException('Native runtime response status must be a non-empty string.');
        }

        $resultOperation = $response['operation'] ?? $operation;

        if (! is_string($resultOperation) || trim($resultOperation) === '') {
            throw new RuntimeException('Native runtime response operation must be a non-empty string.');
        }

        $payload = $response['payload'] ?? [];

        if (! is_array($payload) || ($payload !== [] && array_is_list($payload))) {
            throw new RuntimeException('Native runtime response payload must be an associative array.');
        }

        $metrics = $this->metricsFromResponse($response['metrics'] ?? null) ?? $fallbackMetrics;

        return new NativeRuntimeResult(
            operation: $resultOperation,
            status: $status,
            payload: $this->stringKeyedArray($payload, 'Native runtime response payload'),
            metrics: $metrics,
        );
    }

    private function metricsFromResponse(mixed $metrics): ?RuntimeMetrics
    {
        if (! is_array($metrics) || ($metrics !== [] && array_is_list($metrics))) {
            return null;
        }

        $duration = $metrics['duration_ms'] ?? null;
        $memory = $metrics['memory_delta_bytes'] ?? 0;

        if (! is_float($duration) && ! is_int($duration)) {
            return null;
        }

        return new RuntimeMetrics(
            durationMs: (float) $duration,
            memoryDeltaBytes: is_int($memory) ? $memory : 0,
        );
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $payload, string $label): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                throw new RuntimeException($label . ' must use string keys.');
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
