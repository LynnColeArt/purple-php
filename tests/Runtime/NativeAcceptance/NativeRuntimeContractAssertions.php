<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\NativeAcceptance;

use PHPUnit\Framework\Assert;
use Purple\Contracts\Runtime\NativeRuntime;
use Purple\Runtime\NativeRuntimeResult;

final readonly class NativeRuntimeContractAssertions
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function assertPingCompatible(
        NativeRuntime $runtime,
        string $operation = 'runtime.acceptance.ping',
        array $payload = ['tenant_id' => 'tenant-a'],
    ): NativeRuntimeResult {
        $result = $runtime->invoke($operation, $payload);

        Assert::assertSame($operation, $result->operation);
        Assert::assertSame('ok', $result->status);
        Assert::assertSame('native-compatible', $result->payload['answer'] ?? null);
        Assert::assertNotNull($result->metrics);
        Assert::assertGreaterThanOrEqual(0.0, $result->metrics->durationMs);
        Assert::assertSame([
            'operation' => $operation,
            'status' => 'ok',
            'payload' => [
                'answer' => 'native-compatible',
            ],
            'metrics' => [
                'duration_ms' => $result->metrics->durationMs,
                'memory_delta_bytes' => $result->metrics->memoryDeltaBytes,
            ],
        ], $result->toArray());

        return $result;
    }

    /**
     * @return callable(string, array<string, mixed>): array<string, mixed>
     */
    public static function compatibleInvoker(): callable
    {
        return static fn (string $operation, array $payload): array => [
            'operation' => $operation,
            'status' => 'ok',
            'payload' => [
                'answer' => ($payload['tenant_id'] ?? null) === null ? 'missing-tenant' : 'native-compatible',
            ],
            'metrics' => [
                'duration_ms' => 1.0,
                'memory_delta_bytes' => 0,
            ],
        ];
    }
}
