<?php

declare(strict_types=1);

namespace Purple\Runtime\Sandbox;

use JsonException;
use Purple\Agent\AgentTool;
use Purple\Runtime\RuntimeException;
use Purple\Runtime\RuntimeMetrics;
use Throwable;

final readonly class SandboxedToolExecutor
{
    public function __construct(
        private SandboxPolicy $policy = new SandboxPolicy(),
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function execute(AgentTool $tool, array $input): SandboxedToolResult
    {
        $this->policy->assertSideEffectAllowed($tool->definition->sideEffectLevel);
        $this->policy->assertPayloadAllowed('Tool input', $this->payloadSize($input, 'Tool input'));

        $startedAt = microtime(true);
        $memoryBefore = memory_get_usage();

        try {
            $output = $tool->invoke($input);
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('Sandboxed tool "%s" failed: %s', $tool->name(), $exception->getMessage()), 0, $exception);
        }

        $durationMs = (microtime(true) - $startedAt) * 1000.0;
        $metrics = new RuntimeMetrics(
            durationMs: $durationMs,
            memoryDeltaBytes: memory_get_usage() - $memoryBefore,
        );

        $this->policy->assertDurationAllowed($durationMs);
        $this->policy->assertPayloadAllowed('Tool output', $this->payloadSize($output, 'Tool output'));

        return new SandboxedToolResult($output, $metrics);
    }

    private function payloadSize(mixed $payload, string $label): int
    {
        try {
            return strlen(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new RuntimeException($label . ' must be JSON encodable for sandbox execution.', 0, $exception);
        }
    }
}
