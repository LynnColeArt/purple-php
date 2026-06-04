<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime;

use Purple\Runtime\PhpExtensionBridge;
use Purple\Runtime\RuntimeException;
use Purple\Tests\Testing\TestCase;

final class PhpExtensionBridgeTest extends TestCase
{
    public function testInvokesInjectedNativeRuntimeAndNormalizesResult(): void
    {
        $captured = [];
        $bridge = new PhpExtensionBridge(invoker: function (string $operation, array $payload) use (&$captured): array {
            $captured = compact('operation', 'payload');

            return [
                'operation' => $operation,
                'status' => 'ok',
                'payload' => [
                    'answer' => 'ready',
                ],
                'metrics' => [
                    'duration_ms' => 1.5,
                    'memory_delta_bytes' => 32,
                ],
            ];
        });

        $result = $bridge->invoke('runtime.ping', ['tenant_id' => 'tenant-a']);

        $this->assertTrue($bridge->available());
        $this->assertSame('runtime.ping', $captured['operation'] ?? null);
        $this->assertSame(['tenant_id' => 'tenant-a'], $captured['payload'] ?? null);
        $this->assertSame('ok', $result->status);
        $this->assertSame('ready', $result->payload['answer'] ?? null);
        $metrics = $result->metrics;

        if ($metrics === null) {
            self::fail('Expected native runtime metrics.');
        }

        $this->assertSame(1.5, $metrics->durationMs);
        $this->assertSame(32, $metrics->memoryDeltaBytes);
    }

    public function testFailsClosedWhenNoNativeRuntimeIsAvailable(): void
    {
        $bridge = new PhpExtensionBridge(extensionName: 'definitely_missing_purple_native_extension');

        $this->assertFalse($bridge->available());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');

        $bridge->invoke('runtime.ping', []);
    }
}
