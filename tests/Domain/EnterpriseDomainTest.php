<?php

declare(strict_types=1);

namespace Purple\Tests\Domain;

use Purple\Approval\ApprovalRequest;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentRunStatus;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatSession;
use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Domain\Audit\AuditExportRecord;
use Purple\Domain\DataSensitivity;
use Purple\Domain\EnterpriseContext;
use Purple\Domain\InMemory\InMemoryEnterpriseAdapter;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;
use Purple\Tests\Testing\TestCase;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

final class EnterpriseDomainTest extends TestCase
{
    public function testWorkflowPortsCoverEnterpriseUseCases(): void
    {
        $adapter = new InMemoryEnterpriseAdapter();
        $context = $this->context();

        $content = $adapter->searchContent('returns', $context)[0];
        $contentDraft = $adapter->draftContentRevision($content->id, 'Make friendlier.', $context);
        $catalog = $adapter->searchCatalog('cardigan', $context)[0];
        $catalogDraft = $adapter->draftCatalogUpdate($catalog->sku, ['title' => 'Updated title'], $context);
        $order = $adapter->lookupOrder('ORDER-1', $context);
        $support = $adapter->classifyTicket('Checkout failed', 'Customer cannot pay.', $context);
        $approval = $adapter->requestApproval(new ApprovalRequest(
            id: 'approval-1',
            runId: 'run-123',
            toolName: 'catalog.update',
            reason: 'Draft catalog update requires approval.',
            metadata: $context->policyMetadata(ToolSideEffectLevel::Write),
        ), $context);
        $auditRecord = new AuditExportRecord(
            eventType: 'approval.completed',
            runId: 'run-123',
            context: $context,
            payload: ['approved' => $approval->approved],
        );
        $adapter->recordExternalAuditEvent($auditRecord);

        $this->assertSame('content-1', $content->id);
        $this->assertSame($content->id, $contentDraft->targetId);
        $this->assertSame('SKU-1', $catalog->sku);
        $this->assertSame($catalog->sku, $catalogDraft->targetId);
        $this->assertSame('ORDER-1', $order->orderId);
        $this->assertSame('high', $support->priority);
        $this->assertTrue($approval->approved);
        $this->assertSame('Approved for tenant tenant-a.', $approval->reason);
        $this->assertSame([$auditRecord], $adapter->auditRecords());
    }

    public function testDomainExamplesCanFeedSmartFunctionsChatAndAgents(): void
    {
        $adapter = new InMemoryEnterpriseAdapter();
        $context = $this->context();
        $catalog = $adapter->searchCatalog('cardigan', $context)[0];
        $smartFunction = new SmartFunctionDefinition(
            name: 'catalog.summary',
            providerName: 'fake',
            model: 'fake-model',
            provider: FakeProvider::replying('{"summary":"Domain-fed smart function output."}'),
            prompt: new StringPromptTemplate('Summarize {{ title }} as JSON.'),
            validator: new JsonSchemaValidator(),
            outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-domain-smart-' . bin2hex(random_bytes(4)) . '.jsonl'),
        );
        $chat = new ChatSession(
            name: 'catalog.chat',
            providerName: 'fake',
            model: 'fake-model',
            provider: FakeProvider::replying('Chat saw ' . $catalog->sku . '.'),
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-domain-chat-' . bin2hex(random_bytes(4)) . '.jsonl'),
        );
        $agentProvider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.search","input":{"query":"cardigan"}}'),
            new ProviderResponse('{"action":"complete","answer":"Agent used catalog port."}'),
        ]);
        $agent = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $agentProvider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.search'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-domain-agent-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry([
                new AgentTool(
                    new ToolDefinition(
                        name: 'catalog.search',
                        description: 'Search the enterprise catalog port.',
                        inputSchema: '{}',
                        outputSchema: '{}',
                        sideEffectLevel: ToolSideEffectLevel::Read,
                    ),
                    static fn (array $input): array => [
                        'sku' => $catalog->sku,
                        'query' => $input['query'] ?? '',
                    ],
                ),
            ]),
        );

        $smartOutput = $smartFunction->run(['title' => $catalog->title]);
        $chatOutput = $chat->send('Discuss ' . $catalog->sku . '.');
        $agentOutput = $agent->run('Search catalog.');

        if (!is_array($smartOutput) || !isset($smartOutput['summary']) || !is_string($smartOutput['summary'])) {
            $this->fail('Smart function output must include a summary string.');
        }

        $this->assertSame('Domain-fed smart function output.', $smartOutput['summary']);
        $this->assertSame('Chat saw SKU-1.', $chatOutput->content);
        $this->assertSame(AgentRunStatus::Completed, $agentOutput->status);
    }

    public function testPolicyCanInspectEnterpriseContextMetadata(): void
    {
        $context = new EnterpriseContext(
            tenantId: 'tenant-a',
            userId: 'user-42',
            dataSensitivity: DataSensitivity::Restricted,
            retentionDays: 7,
            providerRoute: 'private-model',
        );
        $policy = new class () implements PolicyEngine {
            public function decide(PolicyRequest $request): PolicyDecision
            {
                return ($request->metadata['data_sensitivity'] ?? null) === 'restricted'
                    ? PolicyDecision::deny('Restricted data requires a private route.')
                    : PolicyDecision::allow();
            }
        };

        $decision = $policy->decide(new PolicyRequest(
            operation: 'domain.workflow',
            provider: 'fake',
            model: 'fake-model',
            metadata: $context->policyMetadata(ToolSideEffectLevel::Read),
        ));

        $this->assertFalse($decision->allowed);
        $this->assertSame('tenant-a', $context->policyMetadata()['tenant_id']);
        $this->assertSame('read', $context->policyMetadata(ToolSideEffectLevel::Read)['side_effect_level']);
    }

    public function testAuditExportShapeIsStable(): void
    {
        $record = new AuditExportRecord(
            eventType: 'agent.tool.completed',
            runId: 'run-123',
            context: $this->context(),
            payload: ['tool' => 'catalog.search'],
        );
        $payload = $record->toExportPayload();

        $this->assertSame('agent.tool.completed', $payload['event_type']);
        $this->assertSame('tenant-a', $payload['tenant_id']);
        $this->assertSame('user-42', $payload['user_id']);
        $this->assertSame('internal', $payload['data_sensitivity']);
        $this->assertSame(['tool' => 'catalog.search'], $payload['payload']);
    }

    private function context(): EnterpriseContext
    {
        return new EnterpriseContext('tenant-a', 'user-42', providerRoute: 'default');
    }
}
