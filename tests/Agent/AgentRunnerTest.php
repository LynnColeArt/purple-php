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
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Domain\EnterpriseContext;
use Purple\Policy\BasicPolicyEngine;
use Purple\Security\PiiRedactor;
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

    public function testToolRetriesAreReplayableInRunResult(): void
    {
        $attempts = 0;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"Recovered after retry."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool(
                new ToolDefinition(
                    name: 'catalog.lookup',
                    description: 'Look up catalog metadata.',
                    inputSchema: '{}',
                    outputSchema: '{}',
                    sideEffectLevel: ToolSideEffectLevel::Read,
                    maxRetries: 1,
                ),
                static function () use (&$attempts): array {
                    $attempts++;

                    if ($attempts === 1) {
                        throw new \RuntimeException('Temporary catalog outage.');
                    }

                    return ['title' => 'Merino cardigan'];
                },
            ),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-tool-retry-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
        );

        $result = $runner->run('Summarize SKU-1.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame(2, $result->toolCalls);
        $this->assertCount(2, $result->toolLog);
        $this->assertSame('failed', $result->toolLog[0]->status);
        $this->assertSame(1, $result->toolLog[0]->attempt);
        $this->assertSame('completed', $result->toolLog[1]->status);
        $this->assertSame(2, $result->toolLog[1]->attempt);
        $this->assertSame('completed', $result->state['status'] ?? null);
        $stateToolLog = $result->state['tool_log'] ?? null;
        $this->assertIsArray($stateToolLog);
        $this->assertCount(2, $stateToolLog);
    }

    public function testToolOutputSchemaValidationCanRecoverWithRetry(): void
    {
        $attempts = 0;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"Validated output."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool(
                new ToolDefinition(
                    name: 'catalog.lookup',
                    description: 'Look up catalog metadata.',
                    inputSchema: '{}',
                    outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
                    sideEffectLevel: ToolSideEffectLevel::Read,
                    maxRetries: 1,
                ),
                static function () use (&$attempts): array {
                    $attempts++;

                    return $attempts === 1 ? [] : ['title' => 'Merino cardigan'];
                },
            ),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-tool-validation-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
        );

        $result = $runner->run('Summarize SKU-1.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('validation_failed', $result->toolLog[0]->status);
        $this->assertSame('completed', $result->toolLog[1]->status);
    }

    public function testProviderFailuresUseRetryBudget(): void
    {
        $attempts = 0;
        $provider = new class ($attempts) implements Provider {
            /** @var list<ProviderRequest> */
            private array $requests = [];

            public function __construct(private int &$attempts)
            {
            }

            public function complete(ProviderRequest $request): ProviderResponse
            {
                $this->requests[] = $request;
                $this->attempts++;

                if ($this->attempts === 1) {
                    throw new \RuntimeException('Provider temporarily unavailable.');
                }

                return new ProviderResponse('{"action":"complete","answer":"Recovered provider."}');
            }

            /**
             * @return list<ProviderRequest>
             */
            public function requests(): array
            {
                return $this->requests;
            }
        };
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-provider-retry-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry(),
            limits: new AgentLimits(maxProviderRetries: 1),
        );

        $result = $runner->run('Answer once.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('Recovered provider.', $result->answer);
        $this->assertSame(2, $attempts);
        $this->assertCount(2, $provider->requests());
    }

    public function testEnterpriseMetadataAndRedactionReachAgentProviderAndReplayLog(): void
    {
        $context = new EnterpriseContext('tenant-a', 'user-42', providerRoute: 'private-model', dataResidencyRegion: 'us');
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1","email":"customer@example.com"}}'),
            new ProviderResponse('{"action":"complete","answer":"Done."}'),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(
                allowedProviders: ['fake', 'catalog.lookup'],
                allowedModels: ['fake-model', 'read'],
                allowedTenantIds: ['tenant-a'],
                allowedProviderRoutes: ['private-model'],
                allowedDataResidencyRegions: ['us'],
            ),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-enterprise-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry([
                new AgentTool(
                    new ToolDefinition(
                        name: 'catalog.lookup',
                        description: 'Look up catalog metadata.',
                        inputSchema: '{}',
                        outputSchema: '{}',
                        sideEffectLevel: ToolSideEffectLevel::Read,
                    ),
                    static fn (): array => [
                        'contact' => 'customer@example.com',
                    ],
                ),
            ]),
            metadata: $context->policyMetadata(),
            redactor: new PiiRedactor(),
        );

        $result = $runner->run('Find customer@example.com.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('tenant-a', $provider->requests()[0]->metadata['tenant_id'] ?? null);
        $this->assertStringContainsString('[redacted-email]', $provider->requests()[0]->messages[0]['content']);
        $this->assertSame('[redacted-email]', $result->toolLog[0]->input['email'] ?? null);
        $output = $result->toolLog[0]->output;
        $this->assertIsArray($output);
        $this->assertSame('[redacted-email]', $output['contact'] ?? null);
        $metadata = $result->state['metadata'] ?? null;
        $this->assertIsArray($metadata);
        $this->assertSame('us', $metadata['data_residency_region'] ?? null);
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
