<?php

declare(strict_types=1);

namespace Purple\Agent;

use Purple\Approval\ApprovalProvider;
use Purple\Approval\ApprovalRequest;
use Purple\Contracts\Audit\AuditEvent;
use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Policy\PolicyDecision;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Schema\SchemaValidator;
use Purple\Contracts\Security\DataRedactor;
use Purple\Hooks\HookAction;
use Purple\Hooks\HookDispatcher;
use Purple\Hooks\HookEvent;
use Purple\Hooks\HookResult;
use Purple\Schema\JsonSchemaValidator;
use Throwable;

final class AgentRunner
{
    private AgentLimits $limits;
    private HookDispatcher $hooks;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $name,
        private readonly string $providerName,
        private readonly string $model,
        private readonly Provider $provider,
        private readonly PolicyEngine $policy,
        private readonly AuditLog $auditLog,
        private readonly AgentToolRegistry $tools,
        private readonly ?ApprovalProvider $approvalProvider = null,
        private readonly ?SchemaValidator $validator = null,
        private readonly array $metadata = [],
        private readonly ?DataRedactor $redactor = null,
        ?HookDispatcher $hooks = null,
        ?AgentLimits $limits = null,
    ) {
        if (trim($this->name) === '') {
            throw new AgentException('Agent name must not be empty.');
        }

        $this->hooks = $hooks ?? new HookDispatcher();
        $this->limits = $limits ?? new AgentLimits();
    }

    public function run(string $goal): AgentRunResult
    {
        if (trim($goal) === '') {
            throw new AgentException('Agent goal must not be empty.');
        }

        $runId = bin2hex(random_bytes(8));
        $startedAt = microtime(true);
        $steps = 0;
        $toolCalls = 0;
        $toolLog = [];
        $messages = [
            [
                'role' => 'user',
                'content' => $goal,
            ],
        ];

        $this->audit('agent.started', $runId, [
            'agent' => $this->name,
            'goal' => $goal,
            ...$this->metadata,
        ]);

        $beforeRun = $this->hooks->dispatch(new HookEvent('before_run', $runId, [
            'agent' => $this->name,
            'goal' => $goal,
        ]));
        $hookOutcome = $this->terminalHookResult($beforeRun, $runId, $steps, $toolCalls, $toolLog);

        if ($hookOutcome !== null) {
            return $hookOutcome;
        }

        if (isset($beforeRun->modifications['goal']) && is_string($beforeRun->modifications['goal'])) {
            $messages[0]['content'] = $beforeRun->modifications['goal'];
        }

        for ($step = 1; $step <= $this->limits->maxSteps; $step++) {
            if ($this->timeBudgetExceeded($startedAt)) {
                return $this->budgetExceeded($runId, $steps, $toolCalls, 'Agent time budget exceeded.', $toolLog);
            }

            $steps = $step;
            $this->auditWarnings($beforeRun, $runId);
            $stepHook = $this->hooks->dispatch(new HookEvent('before_agent_step', $runId, [
                'agent' => $this->name,
                'step' => $step,
            ]));
            $hookOutcome = $this->terminalHookResult($stepHook, $runId, $steps, $toolCalls, $toolLog);

            if ($hookOutcome !== null) {
                return $hookOutcome;
            }

            $this->audit('agent.step.started', $runId, [
                'agent' => $this->name,
                'step' => $step,
                ...$this->metadata,
            ]);

            $providerDecision = $this->decidePolicy('agent.provider_request', $this->providerName, $this->model, [
                'agent' => $this->name,
                'step' => $step,
                ...$this->metadata,
            ]);
            $this->auditPolicyDecision('agent.policy_decided', $runId, $providerDecision, $this->providerName, $this->model);

            if (! $providerDecision->allowed) {
                return $this->policyDenied($runId, $steps, $toolCalls, $providerDecision->reason ?? 'Provider policy denied.', $toolLog);
            }

            $response = $this->providerResponse($runId, $step, $messages, $steps, $toolCalls, $toolLog);

            if ($response instanceof AgentRunResult) {
                return $response;
            }

            try {
                $instruction = AgentInstruction::fromProviderContent($response->content);
            } catch (AgentException $exception) {
                return $this->failed($runId, $steps, $toolCalls, $exception->getMessage(), $toolLog);
            }

            if ($instruction->action === 'complete') {
                $answer = $instruction->answer ?? '';
                $this->audit('agent.completed', $runId, [
                    'agent' => $this->name,
                    'status' => 'completed',
                    'steps' => $steps,
                    'tool_calls' => $toolCalls,
                    ...$this->metadata,
                ]);
                $this->hooks->dispatch(new HookEvent('after_run', $runId, [
                    'agent' => $this->name,
                    'status' => 'completed',
                ]));

                return new AgentRunResult(
                    AgentRunStatus::Completed,
                    $runId,
                    $steps,
                    $toolCalls,
                    answer: $answer,
                    toolLog: $toolLog,
                    state: $this->state('completed', $steps, $toolCalls, $toolLog),
                );
            }

            $toolResult = $this->executeToolInstruction($runId, $instruction, $messages, $steps, $toolCalls, $toolLog);

            if ($toolResult instanceof AgentRunResult) {
                return $toolResult;
            }

            $messages = $toolResult['messages'];
            $toolCalls = $toolResult['tool_calls'];
            $toolLog = $toolResult['tool_log'];

            $afterStep = $this->hooks->dispatch(new HookEvent('after_agent_step', $runId, [
                'agent' => $this->name,
                'step' => $step,
            ]));
            $hookOutcome = $this->terminalHookResult($afterStep, $runId, $steps, $toolCalls, $toolLog);

            if ($hookOutcome !== null) {
                return $hookOutcome;
            }
        }

        return $this->budgetExceeded($runId, $steps, $toolCalls, 'Agent step budget exceeded.', $toolLog);
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function providerResponse(
        string $runId,
        int $step,
        array $messages,
        int $steps,
        int $toolCalls,
        array $toolLog,
    ): ProviderResponse|AgentRunResult {
        $attempt = 0;

        while (true) {
            $attempt++;
            $beforeProvider = $this->hooks->dispatch(new HookEvent('before_provider_request', $runId, [
                'agent' => $this->name,
                'step' => $step,
                'attempt' => $attempt,
            ]));
            $hookOutcome = $this->terminalHookResult($beforeProvider, $runId, $steps, $toolCalls, $toolLog);

            if ($hookOutcome !== null) {
                return $hookOutcome;
            }

            try {
                $response = $this->provider->complete(new ProviderRequest(
                    model: $this->model,
                    messages: $this->redactedMessages($messages),
                    metadata: [
                        'agent' => $this->name,
                        'run_id' => $runId,
                        'step' => $step,
                        'attempt' => $attempt,
                        ...$this->metadata,
                    ],
                ));
            } catch (Throwable $exception) {
                $this->audit('agent.provider.failed', $runId, [
                    'agent' => $this->name,
                    'step' => $step,
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                    ...$this->metadata,
                ]);

                if ($attempt <= $this->limits->maxProviderRetries) {
                    $this->audit('agent.provider.retry', $runId, [
                        'agent' => $this->name,
                        'step' => $step,
                        'attempt' => $attempt,
                        ...$this->metadata,
                    ]);

                    continue;
                }

                return $this->failed($runId, $steps, $toolCalls, 'Agent provider request failed: ' . $exception->getMessage(), $toolLog);
            }

            $this->audit('agent.provider.completed', $runId, [
                'agent' => $this->name,
                'step' => $step,
                'attempt' => $attempt,
                ...$this->metadata,
            ]);
            $afterProvider = $this->hooks->dispatch(new HookEvent('after_provider_response', $runId, [
                'agent' => $this->name,
                'step' => $step,
                'attempt' => $attempt,
                'content' => $response->content,
            ]));
            $hookOutcome = $this->terminalHookResult($afterProvider, $runId, $steps, $toolCalls, $toolLog, allowRetry: true);

            if ($hookOutcome instanceof AgentRunResult) {
                return $hookOutcome;
            }

            if ($afterProvider->action === HookAction::Retry && $attempt <= $this->limits->maxProviderRetries) {
                $this->audit('agent.hook.retry', $runId, [
                    'agent' => $this->name,
                    'step' => $step,
                    'attempt' => $attempt,
                    'reason' => $afterProvider->message,
                ]);
                continue;
            }

            return $response;
        }
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     *
     * @param list<AgentToolCallRecord> $toolLog
     *
     * @return array{messages: list<array{role: string, content: string}>, tool_calls: int, tool_log: list<AgentToolCallRecord>}|AgentRunResult
     */
    private function executeToolInstruction(
        string $runId,
        AgentInstruction $instruction,
        array $messages,
        int $steps,
        int $toolCalls,
        array $toolLog,
    ): array|AgentRunResult {
        $toolName = $instruction->toolName ?? '';
        $tool = $this->tools->get($toolName);
        $validator = $this->validator ?? new JsonSchemaValidator();

        if ($toolCalls >= $this->limits->maxToolCalls) {
            return $this->budgetExceeded($runId, $steps, $toolCalls, 'Agent tool-call budget exceeded.', $toolLog);
        }

        $input = $instruction->input;
        $beforeTool = $this->hooks->dispatch(new HookEvent('before_tool_call', $runId, [
            'agent' => $this->name,
            'tool' => $tool->definition->name,
            'input' => $input,
        ]));
        $hookOutcome = $this->terminalHookResult($beforeTool, $runId, $steps, $toolCalls, $toolLog, approvalToolName: $tool->definition->name);

        if ($hookOutcome instanceof AgentRunResult) {
            return $hookOutcome;
        }

        if (isset($beforeTool->modifications['input']) && is_array($beforeTool->modifications['input']) && ! array_is_list($beforeTool->modifications['input'])) {
            /** @var array<string, mixed> $input */
            $input = $beforeTool->modifications['input'];
        }

        $inputValidation = $validator->validate($input, $tool->definition->inputSchema);

        if (! $inputValidation->valid) {
            return $this->failed(
                $runId,
                $steps,
                $toolCalls,
                'Agent tool input failed schema validation: ' . implode('; ', $inputValidation->violations),
                $toolLog,
            );
        }

        $toolDecision = $this->decidePolicy(
            operation: 'agent.tool_call',
            provider: $tool->definition->name,
            model: $tool->definition->sideEffectLevel->value,
            metadata: [
                'agent' => $this->name,
                'tool' => $tool->definition->name,
                'side_effect_level' => $tool->definition->sideEffectLevel->value,
                'input' => $input,
                ...$this->metadata,
            ],
        );
        $this->auditPolicyDecision(
            'agent.tool.policy_decided',
            $runId,
            $toolDecision,
            $tool->definition->name,
            $tool->definition->sideEffectLevel->value,
        );

        if (! $toolDecision->allowed) {
            return $this->policyDenied($runId, $steps, $toolCalls, $toolDecision->reason ?? 'Tool policy denied.', $toolLog);
        }

        $approval = $this->approvalIfNeeded($runId, $tool, $input, $steps, $toolCalls, $toolLog);

        if ($approval instanceof AgentRunResult) {
            return $approval;
        }

        $attempt = 0;
        $maxAttempts = $tool->definition->maxRetries + 1;
        $output = null;

        while ($attempt < $maxAttempts) {
            if ($toolCalls >= $this->limits->maxToolCalls) {
                return $this->budgetExceeded($runId, $steps, $toolCalls, 'Agent tool-call budget exceeded.', $toolLog);
            }

            $attempt++;
            $this->audit('agent.tool.started', $runId, [
                'agent' => $this->name,
                'tool' => $tool->definition->name,
                'side_effect_level' => $tool->definition->sideEffectLevel->value,
                'attempt' => $attempt,
                ...$this->metadata,
            ]);

            try {
                $output = $tool->invoke($input);
            } catch (Throwable $exception) {
                $toolCalls++;
                $toolLog[] = new AgentToolCallRecord(
                    toolName: $tool->definition->name,
                    input: $this->redactedArray($input),
                    sideEffectLevel: $tool->definition->sideEffectLevel->value,
                    attempt: $attempt,
                    status: 'failed',
                    error: $exception->getMessage(),
                );
                $this->audit('agent.tool.failed', $runId, [
                    'agent' => $this->name,
                    'tool' => $tool->definition->name,
                    'side_effect_level' => $tool->definition->sideEffectLevel->value,
                    'attempt' => $attempt,
                    'tool_calls' => $toolCalls,
                    'error' => $exception->getMessage(),
                    ...$this->metadata,
                ]);

                if ($attempt < $maxAttempts) {
                    $this->audit('agent.tool.retry', $runId, [
                        'agent' => $this->name,
                        'tool' => $tool->definition->name,
                        'attempt' => $attempt,
                        'reason' => $exception->getMessage(),
                        ...$this->metadata,
                    ]);

                    continue;
                }

                return $this->failed($runId, $steps, $toolCalls, 'Agent tool failed: ' . $exception->getMessage(), $toolLog);
            }

            $toolCalls++;
            $outputValidation = $validator->validate($output, $tool->definition->outputSchema);

            if (! $outputValidation->valid) {
                $toolLog[] = new AgentToolCallRecord(
                    toolName: $tool->definition->name,
                    input: $this->redactedArray($input),
                    sideEffectLevel: $tool->definition->sideEffectLevel->value,
                    attempt: $attempt,
                    status: 'validation_failed',
                    output: $this->redactedValue($output),
                    error: implode('; ', $outputValidation->violations),
                );
                $this->audit('agent.tool.validation_failed', $runId, [
                    'agent' => $this->name,
                    'tool' => $tool->definition->name,
                    'side_effect_level' => $tool->definition->sideEffectLevel->value,
                    'attempt' => $attempt,
                    'tool_calls' => $toolCalls,
                    'violations' => $outputValidation->violations,
                    ...$this->metadata,
                ]);

                if ($attempt < $maxAttempts) {
                    $this->audit('agent.tool.retry', $runId, [
                        'agent' => $this->name,
                        'tool' => $tool->definition->name,
                        'attempt' => $attempt,
                        'reason' => 'Tool output failed schema validation.',
                        ...$this->metadata,
                    ]);

                    continue;
                }

                return $this->failed(
                    $runId,
                    $steps,
                    $toolCalls,
                    'Agent tool output failed schema validation: ' . implode('; ', $outputValidation->violations),
                    $toolLog,
                );
            }

            $toolLog[] = new AgentToolCallRecord(
                toolName: $tool->definition->name,
                input: $this->redactedArray($input),
                sideEffectLevel: $tool->definition->sideEffectLevel->value,
                attempt: $attempt,
                status: 'completed',
                output: $this->redactedValue($output),
            );
            $this->audit('agent.tool.completed', $runId, [
                'agent' => $this->name,
                'tool' => $tool->definition->name,
                'side_effect_level' => $tool->definition->sideEffectLevel->value,
                'attempt' => $attempt,
                'tool_calls' => $toolCalls,
                ...$this->metadata,
            ]);
            $afterTool = $this->hooks->dispatch(new HookEvent('after_tool_call', $runId, [
                'agent' => $this->name,
                'tool' => $tool->definition->name,
                'output' => $output,
                'attempt' => $attempt,
            ]));
            $hookOutcome = $this->terminalHookResult($afterTool, $runId, $steps, $toolCalls, $toolLog, approvalToolName: $tool->definition->name);

            if ($hookOutcome instanceof AgentRunResult) {
                return $hookOutcome;
            }

            break;
        }

        $messages[] = [
            'role' => 'assistant',
            'content' => json_encode([
                'action' => 'tool',
                'tool' => $tool->definition->name,
                'input' => $input,
            ], JSON_THROW_ON_ERROR),
        ];
        $messages[] = [
            'role' => 'tool',
            'content' => json_encode([
                'tool' => $tool->definition->name,
                'output' => $output,
            ], JSON_THROW_ON_ERROR),
        ];

        return [
            'messages' => $messages,
            'tool_calls' => $toolCalls,
            'tool_log' => $toolLog,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function approvalIfNeeded(
        string $runId,
        AgentTool $tool,
        array $input,
        int $steps,
        int $toolCalls,
        array $toolLog,
    ): ?AgentRunResult {
        if (! $tool->definition->approvalRequired) {
            return null;
        }

        $request = new ApprovalRequest(
            id: bin2hex(random_bytes(8)),
            runId: $runId,
            toolName: $tool->definition->name,
            reason: 'Tool requires approval before execution.',
            metadata: [
                'input' => $input,
                'side_effect_level' => $tool->definition->sideEffectLevel->value,
                ...$this->metadata,
            ],
        );
        $beforeApproval = $this->hooks->dispatch(new HookEvent('before_approval_request', $runId, [
            'tool' => $tool->definition->name,
        ]));
        $hookOutcome = $this->terminalHookResult($beforeApproval, $runId, $steps, $toolCalls, $toolLog, approvalToolName: $tool->definition->name);

        if ($hookOutcome instanceof AgentRunResult) {
            return $hookOutcome;
        }

        $this->audit('agent.approval.requested', $runId, [
            'agent' => $this->name,
            'tool' => $tool->definition->name,
            'approval_id' => $request->id,
            ...$this->metadata,
        ]);

        if ($this->approvalProvider === null) {
            return new AgentRunResult(
                AgentRunStatus::ApprovalRequired,
                $runId,
                $steps,
                $toolCalls,
                reason: 'Approval required before tool execution.',
                approvalRequest: $request,
                toolLog: $toolLog,
                state: $this->state('approval_required', $steps, $toolCalls, $toolLog),
            );
        }

        $decision = $this->approvalProvider->decide($request);
        $this->audit('agent.approval.decided', $runId, [
            'agent' => $this->name,
            'tool' => $tool->definition->name,
            'approval_id' => $request->id,
            'approved' => $decision->approved,
            'reason' => $decision->reason,
            ...$this->metadata,
        ]);
        $afterApproval = $this->hooks->dispatch(new HookEvent('after_approval_decision', $runId, [
            'tool' => $tool->definition->name,
            'approved' => $decision->approved,
        ]));
        $hookOutcome = $this->terminalHookResult($afterApproval, $runId, $steps, $toolCalls, $toolLog, approvalToolName: $tool->definition->name);

        if ($hookOutcome instanceof AgentRunResult) {
            return $hookOutcome;
        }

        if (! $decision->approved) {
            return new AgentRunResult(
                AgentRunStatus::ApprovalRequired,
                $runId,
                $steps,
                $toolCalls,
                reason: $decision->reason ?? 'Approval denied.',
                approvalRequest: $request,
                toolLog: $toolLog,
                state: $this->state('approval_required', $steps, $toolCalls, $toolLog),
            );
        }

        return null;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function decidePolicy(string $operation, string $provider, string $model, array $metadata): PolicyDecision
    {
        return $this->policy->decide(new PolicyRequest($operation, $provider, $model, $metadata));
    }

    private function auditPolicyDecision(
        string $event,
        string $runId,
        PolicyDecision $decision,
        string $provider,
        string $model,
    ): void {
        $this->audit($event, $runId, [
            'agent' => $this->name,
            'provider' => $provider,
            'model' => $model,
            'allowed' => $decision->allowed,
            'reason' => $decision->reason,
            ...$this->metadata,
        ]);
    }

    /**
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function terminalHookResult(
        HookResult $result,
        string $runId,
        int $steps,
        int $toolCalls,
        array $toolLog,
        bool $allowRetry = false,
        ?string $approvalToolName = null,
    ): ?AgentRunResult {
        $this->auditWarnings($result, $runId);

        if ($allowRetry && $result->action === HookAction::Retry) {
            return null;
        }

        if ($result->action === HookAction::RequireApproval) {
            $request = new ApprovalRequest(
                id: bin2hex(random_bytes(8)),
                runId: $runId,
                toolName: $approvalToolName ?? 'hook',
                reason: $result->message ?? 'Hook requires approval.',
            );

            return new AgentRunResult(
                AgentRunStatus::ApprovalRequired,
                $runId,
                $steps,
                $toolCalls,
                reason: $request->reason,
                approvalRequest: $request,
                toolLog: $toolLog,
                state: $this->state('approval_required', $steps, $toolCalls, $toolLog),
            );
        }

        if ($result->action === HookAction::Block || $result->action === HookAction::Fail) {
            return $this->failed($runId, $steps, $toolCalls, $result->message ?? 'Hook stopped the run.', $toolLog);
        }

        return null;
    }

    /**
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function policyDenied(string $runId, int $steps, int $toolCalls, string $reason, array $toolLog): AgentRunResult
    {
        $this->hooks->dispatch(new HookEvent('policy_violation', $runId, [
            'agent' => $this->name,
            'reason' => $reason,
        ]));
        $this->audit('agent.failed', $runId, [
            'agent' => $this->name,
            'status' => 'policy_denied',
            'reason' => $reason,
            ...$this->metadata,
        ]);

        return new AgentRunResult(
            AgentRunStatus::PolicyDenied,
            $runId,
            $steps,
            $toolCalls,
            reason: $reason,
            toolLog: $toolLog,
            state: $this->state('policy_denied', $steps, $toolCalls, $toolLog),
        );
    }

    /**
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function budgetExceeded(string $runId, int $steps, int $toolCalls, string $reason, array $toolLog): AgentRunResult
    {
        $this->hooks->dispatch(new HookEvent('budget_exceeded', $runId, [
            'agent' => $this->name,
            'reason' => $reason,
        ]));
        $this->audit('agent.budget_exceeded', $runId, [
            'agent' => $this->name,
            'status' => 'budget_exceeded',
            'reason' => $reason,
            ...$this->metadata,
        ]);

        return new AgentRunResult(
            AgentRunStatus::BudgetExceeded,
            $runId,
            $steps,
            $toolCalls,
            reason: $reason,
            toolLog: $toolLog,
            state: $this->state('budget_exceeded', $steps, $toolCalls, $toolLog),
        );
    }

    /**
     * @param list<AgentToolCallRecord> $toolLog
     */
    private function failed(string $runId, int $steps, int $toolCalls, string $reason, array $toolLog): AgentRunResult
    {
        $this->hooks->dispatch(new HookEvent('run_failed', $runId, [
            'agent' => $this->name,
            'reason' => $reason,
        ]));
        $this->audit('agent.failed', $runId, [
            'agent' => $this->name,
            'status' => 'failed',
            'reason' => $reason,
            ...$this->metadata,
        ]);

        return new AgentRunResult(
            AgentRunStatus::Failed,
            $runId,
            $steps,
            $toolCalls,
            reason: $reason,
            toolLog: $toolLog,
            state: $this->state('failed', $steps, $toolCalls, $toolLog),
        );
    }

    /**
     * @param list<AgentToolCallRecord> $toolLog
     *
     * @return array<string, mixed>
     */
    private function state(string $status, int $steps, int $toolCalls, array $toolLog): array
    {
        return [
            'agent' => $this->name,
            'provider' => $this->providerName,
            'model' => $this->model,
            'status' => $status,
            'steps' => $steps,
            'tool_calls' => $toolCalls,
            'metadata' => $this->metadata,
            'tool_log' => array_map(
                static fn (AgentToolCallRecord $record): array => $record->toArray(),
                $toolLog,
            ),
            'limits' => [
                'max_steps' => $this->limits->maxSteps,
                'max_tool_calls' => $this->limits->maxToolCalls,
                'max_provider_retries' => $this->limits->maxProviderRetries,
                'max_seconds' => $this->limits->maxSeconds,
            ],
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     *
     * @return list<array{role: string, content: string}>
     */
    private function redactedMessages(array $messages): array
    {
        if ($this->redactor === null) {
            return $messages;
        }

        return array_map(
            function (array $message): array {
                $content = $this->redactor->redact($message['content']);

                return [
                    'role' => $message['role'],
                    'content' => is_string($content) ? $content : $message['content'],
                ];
            },
            $messages,
        );
    }

    /**
     * @param array<string, mixed> $value
     *
     * @return array<string, mixed>
     */
    private function redactedArray(array $value): array
    {
        $redacted = $this->redactedValue($value);

        if (! is_array($redacted) || array_is_list($redacted)) {
            return $value;
        }

        $result = [];

        foreach ($redacted as $key => $item) {
            if (! is_string($key)) {
                return $value;
            }

            $result[$key] = $item;
        }

        return $result;
    }

    private function redactedValue(mixed $value): mixed
    {
        return $this->redactor?->redact($value) ?? $value;
    }

    private function timeBudgetExceeded(float $startedAt): bool
    {
        return microtime(true) - $startedAt > $this->limits->maxSeconds;
    }

    private function auditWarnings(HookResult $result, string $runId): void
    {
        foreach ($result->warnings as $warning) {
            $this->audit('agent.hook.warning', $runId, [
                'agent' => $this->name,
                'warning' => $warning,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function audit(string $type, string $runId, array $metadata): void
    {
        $this->auditLog->record(AuditEvent::now($type, $runId, $metadata));
    }
}
