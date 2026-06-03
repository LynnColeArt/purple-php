<?php

declare(strict_types=1);

use Purple\Approval\ApprovalRequest;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatSession;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Domain\Audit\AuditExportRecord;
use Purple\Domain\EnterpriseContext;
use Purple\Domain\InMemory\InMemoryEnterpriseAdapter;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

require __DIR__ . '/../../vendor/autoload.php';

$context = new EnterpriseContext('tenant-a', 'user-42', providerRoute: 'default');
$adapter = new InMemoryEnterpriseAdapter();
$catalogItem = $adapter->searchCatalog('cardigan', $context)[0];
$approval = $adapter->requestApproval(new ApprovalRequest(
    id: 'approval-1',
    runId: 'run-domain-example',
    toolName: 'catalog.update',
    reason: 'Draft catalog changes need enterprise approval.',
    metadata: $context->policyMetadata(ToolSideEffectLevel::Write),
), $context);
$auditRecord = new AuditExportRecord(
    eventType: 'approval.completed',
    runId: 'run-domain-example',
    context: $context,
    payload: [
        'approved' => $approval->approved,
        'tool' => 'catalog.update',
    ],
);
$adapter->recordExternalAuditEvent($auditRecord);

$smartFunction = new SmartFunctionDefinition(
    name: 'catalog.summary',
    providerName: 'fake',
    model: 'fake-model',
    provider: FakeProvider::replying('{"summary":"Enterprise catalog summary."}'),
    prompt: new StringPromptTemplate('Summarize {{ title }} as JSON.'),
    validator: new JsonSchemaValidator(),
    outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
    policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/domain-smart.jsonl'),
);
$chat = new ChatSession(
    name: 'catalog.chat',
    providerName: 'fake',
    model: 'fake-model',
    provider: FakeProvider::replying('I can help with ' . $catalogItem->sku . '.'),
    policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/domain-chat.jsonl'),
);
$agent = new AgentRunner(
    name: 'catalog.agent',
    providerName: 'fake',
    model: 'fake-model',
    provider: new FakeProvider([
        new ProviderResponse('{"action":"tool","tool":"catalog.search","input":{"query":"cardigan"}}'),
        new ProviderResponse('{"action":"complete","answer":"Agent used the enterprise catalog port."}'),
    ]),
    policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.search'], allowedModels: ['fake-model', 'read']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/domain-agent.jsonl'),
    tools: new AgentToolRegistry([
        new AgentTool(
            new ToolDefinition(
                name: 'catalog.search',
                description: 'Search catalog through an enterprise workflow port.',
                inputSchema: '{}',
                outputSchema: '{}',
                sideEffectLevel: ToolSideEffectLevel::Read,
            ),
            static fn (): array => ['sku' => $catalogItem->sku, 'title' => $catalogItem->title],
        ),
    ]),
);

print json_encode([
    'context' => $context->policyMetadata(),
    'approval' => [
        'approved' => $approval->approved,
        'reason' => $approval->reason,
    ],
    'audit_export' => $auditRecord->toExportPayload(),
    'audit_records_recorded' => count($adapter->auditRecords()),
    'smart_function' => $smartFunction->run(['title' => $catalogItem->title]),
    'chat' => $chat->send('Discuss ' . $catalogItem->sku)->content,
    'agent' => $agent->run('Use catalog search.')->answer,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
