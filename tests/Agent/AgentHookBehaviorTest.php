<?php

declare(strict_types=1);

namespace Purple\Tests\Agent;

use Purple\Agent\AgentRunner;
use Purple\Agent\AgentRunStatus;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Hooks\HookDispatcher;
use Purple\Hooks\HookEvent;
use Purple\Hooks\HookResult;
use Purple\Hooks\RuntimeHook;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;
use Purple\Tests\Testing\TestCase;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;

final class AgentHookBehaviorTest extends TestCase
{
    public function testHookCanModifyToolInputBeforeExecution(): void
    {
        $seenSku = '';
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"Done."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool($this->tool(), static function (array $input) use (&$seenSku): array {
                $seenSku = is_string($input['sku'] ?? null) ? $input['sku'] : '';

                return ['title' => 'Modified item'];
            }),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-hook-modify-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: $tools,
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

        $result = $runner->run('Look up SKU-1.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('SKU-2', $seenSku);
    }

    public function testHookCanRetryProviderResponse(): void
    {
        $retryUsed = false;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"complete","answer":"First answer."}'),
            new ProviderResponse('{"action":"complete","answer":"Retried answer."}'),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-hook-retry-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry(),
            hooks: new HookDispatcher([
                new class ($retryUsed) implements RuntimeHook {
                    public function __construct(private bool &$retryUsed)
                    {
                    }

                    public function handle(HookEvent $event): HookResult
                    {
                        if ($event->type !== 'after_provider_response' || $this->retryUsed) {
                            return HookResult::allow();
                        }

                        $this->retryUsed = true;

                        return HookResult::retry('Try provider response again.');
                    }
                },
            ]),
        );

        $result = $runner->run('Answer once.');

        $this->assertSame(AgentRunStatus::Completed, $result->status);
        $this->assertSame('Retried answer.', $result->answer);
        $this->assertCount(2, $provider->requests());
    }

    public function testHookModifiedToolInputIsCheckedByPolicyBeforeCallback(): void
    {
        $called = false;
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new class () implements PolicyEngine {
                public function decide(PolicyRequest $request): PolicyDecision
                {
                    if ($request->operation !== 'agent.tool_call') {
                        return PolicyDecision::allow();
                    }

                    $input = $request->metadata['input'] ?? [];

                    if (is_array($input) && ($input['sku'] ?? null) === 'BLOCKED-SKU') {
                        return PolicyDecision::deny('Blocked SKU cannot be used.');
                    }

                    return PolicyDecision::allow();
                }
            },
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-hook-policy-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry([
                new AgentTool($this->tool(), static function () use (&$called): array {
                    $called = true;

                    return ['title' => 'Should not execute'];
                }),
            ]),
            hooks: new HookDispatcher([
                new class () implements RuntimeHook {
                    public function handle(HookEvent $event): HookResult
                    {
                        if ($event->type !== 'before_tool_call') {
                            return HookResult::allow();
                        }

                        return HookResult::modify(['input' => ['sku' => 'BLOCKED-SKU']]);
                    }
                },
            ]),
        );

        $result = $runner->run('Look up SKU-1.');

        $this->assertSame(AgentRunStatus::PolicyDenied, $result->status);
        $this->assertFalse($called);
    }

    public function testAfterToolHookCanStopRunWithReplayLog(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-agent-after-tool-hook-' . bin2hex(random_bytes(4)) . '.jsonl'),
            tools: new AgentToolRegistry([
                new AgentTool($this->tool(), static fn (): array => ['title' => 'Merino cardigan']),
            ]),
            hooks: new HookDispatcher([
                new class () implements RuntimeHook {
                    public function handle(HookEvent $event): HookResult
                    {
                        if ($event->type !== 'after_tool_call') {
                            return HookResult::allow();
                        }

                        return HookResult::block('Stop after tool inspection.');
                    }
                },
            ]),
        );

        $result = $runner->run('Look up SKU-1.');

        $this->assertSame(AgentRunStatus::Failed, $result->status);
        $this->assertSame('Stop after tool inspection.', $result->reason);
        $this->assertCount(1, $result->toolLog);
        $this->assertSame('completed', $result->toolLog[0]->status);
    }

    private function tool(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'catalog.lookup',
            description: 'Look up catalog metadata.',
            inputSchema: '{}',
            outputSchema: '{}',
            sideEffectLevel: ToolSideEffectLevel::Read,
        );
    }
}
