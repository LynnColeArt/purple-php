<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime;

use Purple\Runtime\NativeRuntimeCompatibility;
use Purple\Runtime\NativeRuntimeReadiness;
use Purple\Runtime\PhpExtensionBridge;
use Purple\Tests\Testing\TestCase;

final class NativeRuntimeCompatibilityTest extends TestCase
{
    public function testReportsCompatibleFixtureRuntime(): void
    {
        $report = (new NativeRuntimeCompatibility())->check(new PhpExtensionBridge(
            invoker: NativeRuntimeCompatibility::compatibleFixtureInvoker(),
        ));

        $this->assertTrue($report->compatible);
        $this->assertSame('compatible', $report->status);
        $this->assertSame(NativeRuntimeCompatibility::OPERATION, $report->operation);
        $this->assertSame('native-compatible', $report->payload['answer'] ?? null);
        $this->assertNotNull($report->metrics);
        $this->assertGreaterThanOrEqual(0.0, $report->metrics->durationMs);
        $this->assertSame([
            'compatible' => true,
            'status' => 'compatible',
            'operation' => NativeRuntimeCompatibility::OPERATION,
            'payload' => [
                'answer' => 'native-compatible',
            ],
            'metrics' => [
                'duration_ms' => 1.0,
                'memory_delta_bytes' => 0,
            ],
            'message' => 'Native runtime satisfies the compatibility check.',
        ], $report->toArray());
    }

    public function testReportsIncompatiblePayloadAnswer(): void
    {
        $report = (new NativeRuntimeCompatibility())->check(new PhpExtensionBridge(
            invoker: static fn (string $operation, array $payload): array => [
                'operation' => $operation,
                'status' => 'ok',
                'payload' => [
                    'answer' => 'not-purple',
                ],
                'metrics' => [
                    'duration_ms' => 1.0,
                    'memory_delta_bytes' => 0,
                ],
            ],
        ));

        $this->assertFalse($report->compatible);
        $this->assertSame('incompatible', $report->status);
        $this->assertStringContainsString('Expected native payload answer', $report->message);
    }

    public function testReportsIncompatibleMalformedBridgeResponse(): void
    {
        $report = (new NativeRuntimeCompatibility())->check(new PhpExtensionBridge(
            invoker: static fn (): array => ['not', 'associative'],
        ));

        $this->assertFalse($report->compatible);
        $this->assertSame('incompatible', $report->status);
        $this->assertStringContainsString('associative array', $report->message);
    }

    public function testReportsUnavailableMissingExtension(): void
    {
        $report = (new NativeRuntimeCompatibility())->check(new PhpExtensionBridge(
            extensionName: 'definitely_missing_purple_native_extension',
        ));

        $this->assertFalse($report->compatible);
        $this->assertSame('unavailable', $report->status);
        $this->assertStringContainsString('not available', $report->message);
    }

    public function testReadinessDescribesCompatibilityPrototype(): void
    {
        $description = NativeRuntimeReadiness::describe();
        $compatibility = $description['compatibility'] ?? null;

        $this->assertIsArray($compatibility);
        $this->assertSame(NativeRuntimeCompatibility::class, $compatibility['checker'] ?? null);
        $this->assertSame(NativeRuntimeCompatibility::OPERATION, $compatibility['operation'] ?? null);
        $this->assertTrue($compatibility['fixture_mode'] ?? false);
        $this->assertTrue($compatibility['extension_mode'] ?? false);
        $this->assertSame('prototype', $compatibility['status'] ?? null);
    }
}
