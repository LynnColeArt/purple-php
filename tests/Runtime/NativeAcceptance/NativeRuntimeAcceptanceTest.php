<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\NativeAcceptance;

use Purple\Runtime\NativeRuntimeCompatibility;
use Purple\Runtime\PhpExtensionBridge;
use Purple\Runtime\RuntimeException;
use Purple\Runtime\RuntimeMetrics;
use Purple\Tests\Testing\TestCase;

final class NativeRuntimeAcceptanceTest extends TestCase
{
    public function testPhpExtensionBridgeSatisfiesReusableNativeRuntimeContract(): void
    {
        $bridge = new PhpExtensionBridge(invoker: NativeRuntimeContractAssertions::compatibleInvoker());

        NativeRuntimeContractAssertions::assertPingCompatible($bridge);
    }

    public function testCompatibilityFixtureSatisfiesReusableNativeRuntimeContract(): void
    {
        $bridge = new PhpExtensionBridge(invoker: NativeRuntimeCompatibility::compatibleFixtureInvoker());

        NativeRuntimeContractAssertions::assertPingCompatible($bridge);
    }

    public function testNativeRuntimeRejectsBlankOperation(): void
    {
        $bridge = new PhpExtensionBridge(invoker: NativeRuntimeContractAssertions::compatibleInvoker());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('operation must not be empty');

        $bridge->invoke(' ', []);
    }

    public function testNativeRuntimeRejectsMalformedTopLevelResponse(): void
    {
        $bridge = new PhpExtensionBridge(invoker: static fn (): array => ['not', 'associative']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('response must be an associative array');

        $bridge->invoke('runtime.acceptance.ping', []);
    }

    public function testNativeRuntimeRejectsMalformedPayloadResponse(): void
    {
        $bridge = new PhpExtensionBridge(invoker: static fn (): array => [
            'status' => 'ok',
            'payload' => ['not', 'associative'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('payload must be an associative array');

        $bridge->invoke('runtime.acceptance.ping', []);
    }

    public function testNativeRuntimeRejectsInvalidProvidedMetrics(): void
    {
        $bridge = new PhpExtensionBridge(invoker: static fn (string $operation): array => [
            'operation' => $operation,
            'status' => 'ok',
            'payload' => [],
            'metrics' => [
                'duration_ms' => -1.0,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duration must not be negative');

        $bridge->invoke('runtime.acceptance.ping', []);
    }

    public function testRuntimeMetricsRejectNegativeDuration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duration must not be negative');

        new RuntimeMetrics(durationMs: -0.01);
    }
}
