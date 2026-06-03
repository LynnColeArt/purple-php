<?php

declare(strict_types=1);

namespace Purple\Tests\Agent;

use Purple\Agent\AgentLimits;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentRunStatus;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Approval\StaticApprovalProvider;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;
use Purple\Tests\Testing\TestCase;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

final class AgentRunnerTest extends TestCase
{
    public function testCompletesFakeProviderWorkflowUsingReadOnlyTool(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-agent-' . bin2hex(random_bytes(4)) . '.jsonl';
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"SKU-1 is a merino cardigan."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool($this->tool('catalog.lookup'), static fn (array $input): array => [
                'title' => 'Merino cardigan',
                'sku' => $input['sku'] ?? '',
            ]),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog($auditPath),
            tools: $tools,
        );

        $result = $runner->run('Summarize SKU-1.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('SKU-1 is a merino cardigan.', $result->answer);
        $this->assertSame(2, $result->steps);
        $this->assertSame(1, $result->toolCalls);
        $this->assertCount(2, $provider->requests());

        $audit = implode("\n", file($auditPath, FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertStringContainsString('agent.step.started', $audit);
        $this->assertStringContainsString('agent.tool.completed', $audit);
        $this->assertStringContainsString('agent.completed', $audit);
    }

    public function testApprovalGatedToolStopsBeforeSideEffects(): void
    {
        $called = false;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"order.refund","input":{"order_id":"O-1"}}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool(
                new ToolDefinition(
                    name: 'order.refund',
                    description: 'Refund an order.',
                    inputSchema: '{}',
                    outputSchema: '{}',
                    sideEffectLevel: ToolSideEffectLevel::Write,
                    approvalRequired: true,
                ),
                static function () use (&$called): array {
                    $called = true;

                    return ['refunded' => true];
                },
            ),
        ]);
        $runner = new AgentRunner(
            name: 'support.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'order.refund'], allowedModels: ['fake-model', 'write']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-approval-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
        );

        $result = $runner->run('Refund order O-1.');

        $this->assertSame(AgentRunStatus::ApprovalRequired, $result->status);
        $this->assertSame('order.refund', $result->approvalRequest?->toolName);
        $this->assertFalse($called);
    }

    public function testApprovalProviderCanAllowGatedToolExecution(): void
    {
        $called = false;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"order.refund","input":{"order_id":"O-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"Refund queued."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool(
                new ToolDefinition(
                    name: 'order.refund',
                    description: 'Refund an order.',
                    inputSchema: '{}',
                    outputSchema: '{}',
                    sideEffectLevel: ToolSideEffectLevel::Write,
                    approvalRequired: true,
                ),
                static function () use (&$called): array {
                    $called = true;

                    return ['refunded' => true];
                },
            ),
        ]);
        $runner = new AgentRunner(
            name: 'support.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'order.refund'], allowedModels: ['fake-model', 'write']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-approved-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
            approvalProvider: new StaticApprovalProvider(true),
        );

        $result = $runner->run('Refund order O-1.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertTrue($called);
    }

    public function testStepLimitStopsRunawayLoop(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-2"}}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool($this->tool('catalog.lookup'), static fn (): array => ['title' => 'Merino cardigan']),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-budget-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
            limits: new AgentLimits(maxSteps: 1),
        );

        $result = $runner->run('Keep looking.');

        $this->assertSame(AgentRunStatus::BudgetExceeded, $result->status);
        $this->assertSame(1, $result->steps);
    }

    public function testHooksCannotBypassMandatoryPolicy(): void
    {
        $provider = FakeProvider::replying('{"action":"complete","answer":"Should not run."}');
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['openai']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-policy-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry(),
        );

        $result = $runner->run('Try to bypass policy.');

        $this->assertSame(AgentRunStatus::PolicyDenied, $result->status);
        $this->assertCount(0, $provider->requests());
    }

    private function tool(string $name): ToolDefinition
    {
        return new ToolDefinition(
            name: $name,
            description: 'Look up catalog metadata.',
            inputSchema: '{"type":"object","required":["sku"],"properties":{"sku":{"type":"string"}}}',
            outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
            sideEffectLevel: ToolSideEffectLevel::Read,
        );
    }
}
