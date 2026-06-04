<?php

declare(strict_types=1);

use Purple\Agent\AgentLimits;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Policy\BasicPolicyEngine;
use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Runtime\Sidecar\SidecarEnvelope;
use Purple\Runtime\Sidecar\SidecarProtocol;
use Purple\Testing\FakeProvider;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

require __DIR__ . '/../../vendor/autoload.php';

$provider = new FakeProvider([
    new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
    new ProviderResponse('{"action":"complete","answer":"SKU-1 is ready for sidecar handoff."}'),
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
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/runtime-handoff-agent.jsonl'),
    tools: $tools,
    limits: new AgentLimits(maxSteps: 3),
);

$result = $runner->run('Prepare a catalog brief for sidecar handoff.');
$store = new FileDurableRunStore(__DIR__ . '/../../var/runtime/runs');
$record = new DurableRunRecord(
    runId: $result->runId,
    status: $result->status->value,
    state: [
        ...$result->state,
        'answer' => $result->answer,
        'tool_log' => array_map(static fn ($entry): array => $entry->toArray(), $result->toolLog),
    ],
);
$store->save($record);

$protocol = new SidecarProtocol();
$envelope = new SidecarEnvelope(
    version: SidecarProtocol::VERSION,
    type: 'agent.run.handoff',
    runId: $record->runId,
    payload: [
        'store' => 'file',
        'status' => $record->status,
        'resume_hint' => 'sidecar may load durable state before continuing orchestration',
    ],
);
$encoded = $protocol->encode($envelope);
$sidecarEnvelope = $protocol->decode($encoded);
$loaded = $store->get($sidecarEnvelope->runId);

print json_encode([
    'composer_run' => [
        'run_id' => $record->runId,
        'status' => $record->status,
        'answer' => $record->state['answer'] ?? null,
    ],
    'durable_store' => [
        'loaded' => $loaded !== null,
        'state_keys' => $loaded === null ? [] : array_keys($loaded->state),
    ],
    'sidecar_handoff' => $sidecarEnvelope->toArray(),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
