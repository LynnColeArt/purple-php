<?php

declare(strict_types=1);

namespace Purple\Tests\Chat;

use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatMessage;
use Purple\Chat\ChatPolicyDenied;
use Purple\Chat\ChatSession;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;
use Purple\Tests\Testing\TestCase;

final class ChatSessionTest extends TestCase
{
    public function testSendsMessagesThroughFakeProviderAndMaintainsHistory(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-chat-' . bin2hex(random_bytes(4)) . '.jsonl';
        $provider = new FakeProvider([
            new ProviderResponse('Hello from support.'),
        ]);
        $history = new ChatHistory([
            ChatMessage::system('Answer briefly.'),
        ]);
        $session = new ChatSession(
            name: 'support.chat',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog($auditPath),
            history: $history,
        );

        $response = $session->send('Can you help?');
        $messages = $session->history()->all();
        $providerMessages = $provider->requests()[0]->messages;

        $this->assertSame('Hello from support.', $response->content);
        $this->assertSame(3, $response->history->count());
        $this->assertSame('system', $messages[0]->role);
        $this->assertSame('user', $messages[1]->role);
        $this->assertSame('assistant', $messages[2]->role);
        $this->assertSame('Can you help?', $providerMessages[1]['content']);
        $this->assertCount(1, iterator_to_array($response->chunks()));

        $audit = implode("\n", file($auditPath, FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertStringContainsString('chat.policy_decided', $audit);
        $this->assertStringContainsString('chat.started', $audit);
        $this->assertStringContainsString('chat.completed', $audit);
        $this->assertStringContainsString('"status":"completed"', $audit);
    }

    public function testPolicyDenialBlocksChatProviderInvocation(): void
    {
        $provider = FakeProvider::replying('Should not run.');
        $session = new ChatSession(
            name: 'support.chat',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['openai']),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-chat-deny-' . bin2hex(random_bytes(4)) . '.jsonl'),
        );

        $this->expectException(ChatPolicyDenied::class);

        try {
            $session->send('Can you help?');
        } finally {
            $this->assertSame([], $provider->requests());
            $this->assertSame(0, $session->history()->count());
        }
    }
}
