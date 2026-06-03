<?php

declare(strict_types=1);

namespace Purple\Chat;

use Purple\Contracts\Audit\AuditEvent;
use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Throwable;

final class ChatSession
{
    private ChatHistory $history;

    public function __construct(
        private readonly string $name,
        private readonly string $providerName,
        private readonly string $model,
        private readonly Provider $provider,
        private readonly PolicyEngine $policy,
        private readonly AuditLog $auditLog,
        ?ChatHistory $history = null,
    ) {
        if (trim($this->name) === '') {
            throw new ChatException('Chat session name must not be empty.');
        }

        $this->history = $history ?? new ChatHistory();
    }

    public function history(): ChatHistory
    {
        return $this->history;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function send(string $message, array $metadata = []): ChatResponse
    {
        if (trim($message) === '') {
            throw new ChatException('Chat message must not be empty.');
        }

        $runId = bin2hex(random_bytes(8));
        $policyDecision = $this->policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: $this->providerName,
            model: $this->model,
            metadata: [
                'chat_session' => $this->name,
                ...$metadata,
            ],
        ));

        $this->auditLog->record(AuditEvent::now('chat.policy_decided', $runId, [
            'chat_session' => $this->name,
            'provider' => $this->providerName,
            'model' => $this->model,
            'allowed' => $policyDecision->allowed,
            'reason' => $policyDecision->reason,
        ]));

        if (! $policyDecision->allowed) {
            $this->auditLog->record(AuditEvent::now('chat.failed', $runId, [
                'chat_session' => $this->name,
                'provider' => $this->providerName,
                'model' => $this->model,
                'status' => 'policy_denied',
            ]));

            throw new ChatPolicyDenied($policyDecision->reason ?? 'Policy denied chat execution.');
        }

        $this->history->add(ChatMessage::user($message));

        $this->auditLog->record(AuditEvent::now('chat.started', $runId, [
            'chat_session' => $this->name,
            'provider' => $this->providerName,
            'model' => $this->model,
            'message_count' => $this->history->count(),
        ]));

        try {
            $response = $this->provider->complete(new ProviderRequest(
                model: $this->model,
                messages: $this->history->toProviderMessages(),
                metadata: [
                    'chat_session' => $this->name,
                    'run_id' => $runId,
                    ...$metadata,
                ],
            ));
        } catch (Throwable $exception) {
            $this->auditLog->record(AuditEvent::now('chat.failed', $runId, [
                'chat_session' => $this->name,
                'provider' => $this->providerName,
                'model' => $this->model,
                'status' => 'provider_failed',
                'error' => $exception->getMessage(),
            ]));

            throw new ChatException('Chat provider request failed: ' . $exception->getMessage(), 0, $exception);
        }

        $this->history->add(ChatMessage::assistant($response->content));

        $this->auditLog->record(AuditEvent::now('chat.completed', $runId, [
            'chat_session' => $this->name,
            'provider' => $this->providerName,
            'model' => $this->model,
            'status' => 'completed',
            'message_count' => $this->history->count(),
            'total_tokens' => $response->usage?->totalTokens(),
        ]));

        return new ChatResponse($response->content, $this->history, $response, $runId);
    }
}
