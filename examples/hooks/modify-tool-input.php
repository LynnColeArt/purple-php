<?php

declare(strict_types=1);

use Purple\Agent\AgentRunner;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Hooks\HookDispatcher;
use Purple\Hooks\HookEvent;
use Purple\Hooks\HookResult;
use Purple\Hooks\RuntimeHook;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

require __DIR__ . '/../../vendor/autoload.php';

$provider = new FakeProvider([
    new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
    new ProviderResponse('{"action":"complete","answer":"Hook-modified lookup completed."}'),
]);
$runner = new AgentRunner(
    name: 'catalog.agent',
    providerName: 'fake',
    model: 'fake-model',
    provider: $provider,
    policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/hooks.jsonl'),
    tools: new AgentToolRegistry([
        new AgentTool(
            new ToolDefinition(
                name: 'catalog.lookup',
                description: 'Look up catalog metadata for a SKU.',
                inputSchema: '{}',
                outputSchema: '{}',
                sideEffectLevel: ToolSideEffectLevel::Read,
            ),
            static fn (array $input): array => ['sku_seen' => $input['sku'] ?? ''],
        ),
    ]),
    hooks: new HookDispatcher([
        new class () implements RuntimeHook {
            public function handle(HookEvent $event): HookResult
            {
                if ($event->type !== 'before_tool_call') {
                    return HookResult::allow();
                }

                return HookResult::modify(['input' => ['sku' => 'SKU-2']]);
            }
        },
    ]),
);

$result = $runner->run('Look up SKU-1, but let a hook adjust the input.');

print json_encode([
    'status' => $result->status->value,
    'answer' => $result->answer,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
