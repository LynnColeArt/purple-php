<?php

declare(strict_types=1);

use Purple\Agent\AgentLimits;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

require __DIR__ . '/../../vendor/autoload.php';

$provider = new FakeProvider([
    new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
    new ProviderResponse('{"action":"complete","answer":"SKU-1 is ready for the catalog brief."}'),
]);
$tools = new AgentToolRegistry([
    new AgentTool(
        new ToolDefinition(
            name: 'catalog.lookup',
            description: 'Look up catalog metadata for a SKU.',
            inputSchema: '{"type":"object","required":["sku"],"properties":{"sku":{"type":"string"}}}',
            outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
            sideEffectLevel: ToolSideEffectLevel::Read,
        ),
        static fn (array $input): array => [
            'sku' => $input['sku'] ?? '',
            'title' => 'Merino travel cardigan',
        ],
    ),
]);
$runner = new AgentRunner(
    name: 'catalog.agent',
    providerName: 'fake',
    model: 'fake-model',
    provider: $provider,
    policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/agent.jsonl'),
    tools: $tools,
    limits: new AgentLimits(maxSteps: 3),
);

$result = $runner->run('Prepare a catalog brief for SKU-1.');

print json_encode([
    'status' => $result->status->value,
    'answer' => $result->answer,
    'steps' => $result->steps,
    'tool_calls' => $result->toolCalls,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
