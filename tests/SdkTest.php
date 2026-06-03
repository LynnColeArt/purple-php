<?php

declare(strict_types=1);

namespace Purple\Tests;

use InvalidArgumentException;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatMessage;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Sdk;
use Purple\Testing\FakeProvider;
use Purple\Tests\Testing\TestCase;

final class SdkTest extends TestCase
{
    private const SUMMARY_SCHEMA = <<<'JSON'
{
  "type": "object",
  "required": ["summary"],
  "properties": {
    "summary": {"type": "string"}
  }
}
JSON;

    public function testCreatesSmartFunctionWithCommonDefaults(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-sdk-smart-function-' . bin2hex(random_bytes(4)) . '.jsonl';
        $provider = FakeProvider::replying('{"summary":"SDK summary."}');
        $sdk = new Sdk(
            provider: $provider,
            providerName: 'fake',
            model: 'fake-model',
            auditLog: new FileAuditLog($auditPath),
        );

        $function = $sdk->smartFunction(
            name: 'catalog.summary',
            prompt: 'Summarize {{ title }} as JSON.',
            outputSchema: self::SUMMARY_SCHEMA,
        );

        $this->assertSame(['summary' => 'SDK summary.'], $function->run(['title' => 'Merino cardigan']));
        $this->assertCount(1, $provider->requests());

        $audit = implode("\n", file($auditPath, FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertStringContainsString('smart_function.completed', $audit);
    }

    public function testCreatesChatSessionWithCommonDefaults(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('Hello from the SDK.'),
        ]);
        $sdk = new Sdk(
            provider: $provider,
            providerName: 'fake',
            model: 'fake-model',
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-chat-' . bin2hex(random_bytes(4)) . '.jsonl'),
        );

        $session = $sdk->chatSession('support.chat', new ChatHistory([
            ChatMessage::system('Answer briefly.'),
        ]));
        $response = $session->send('Can you help?');

        $this->assertSame('Hello from the SDK.', $response->content);
        $this->assertSame(3, $session->history()->count());
        $this->assertSame('Can you help?', $provider->requests()[0]->messages[1]['content']);
    }

    public function testRejectsBlankProviderIdentity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider name must not be empty.');

        new Sdk(
            provider: FakeProvider::replying('{"summary":"Nope."}'),
            providerName: ' ',
            model: 'fake-model',
        );
    }
}
