<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\Sandbox;

use Purple\Agent\AgentTool;
use Purple\Runtime\RuntimeException;
use Purple\Runtime\Sandbox\SandboxPolicy;
use Purple\Runtime\Sandbox\SandboxedToolExecutor;
use Purple\Tests\Testing\TestCase;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

final class SandboxedToolExecutorTest extends TestCase
{
    public function testExecutesAllowedToolAndReportsMetrics(): void
    {
        $tool = new AgentTool(
            definition: new ToolDefinition(
                name: 'catalog.lookup',
                description: 'Look up catalog data.',
                inputSchema: '{}',
                outputSchema: '{}',
                sideEffectLevel: ToolSideEffectLevel::Read,
            ),
            callback: static fn (array $input): array => ['sku' => $input['sku'] ?? null, 'title' => 'Travel cardigan'],
        );

        $result = (new SandboxedToolExecutor())->execute($tool, ['sku' => 'SKU-1']);
        $output = $result->output;

        $this->assertIsArray($output);
        $this->assertSame('Travel cardigan', $output['title'] ?? null);
        $this->assertGreaterThanOrEqual(0.0, $result->metrics->durationMs);
        $this->assertArrayHasKey('metrics', $result->toArray());
    }

    public function testBlocksDisallowedSideEffectLevel(): void
    {
        $tool = new AgentTool(
            definition: new ToolDefinition(
                name: 'catalog.publish',
                description: 'Publish catalog data.',
                inputSchema: '{}',
                outputSchema: '{}',
                sideEffectLevel: ToolSideEffectLevel::Write,
            ),
            callback: static fn (): array => ['published' => true],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not allowed');

        (new SandboxedToolExecutor())->execute($tool, []);
    }

    public function testBlocksOversizedPayload(): void
    {
        $tool = new AgentTool(
            definition: new ToolDefinition(
                name: 'catalog.lookup',
                description: 'Look up catalog data.',
                inputSchema: '{}',
                outputSchema: '{}',
            ),
            callback: static fn (): array => ['ok' => true],
        );
        $executor = new SandboxedToolExecutor(new SandboxPolicy(maxPayloadBytes: 10));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('payload limit');

        $executor->execute($tool, ['query' => str_repeat('x', 20)]);
    }
}
