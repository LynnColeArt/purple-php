<?php

declare(strict_types=1);

namespace Purple\Tests;

use InvalidArgumentException;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatMessage;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\ProviderProfile;
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

    public function testCreatesFakeSdkFromProviderProfile(): void
    {
        $provider = FakeProvider::replying('{"summary":"Factory summary."}');
        $sdk = Sdk::fake(
            profile: ProviderProfile::fake(),
            provider: $provider,
        );

        $function = $sdk->smartFunction(
            name: 'catalog.summary',
            prompt: 'Summarize {{ title }} as JSON.',
            outputSchema: self::SUMMARY_SCHEMA,
        );

        $this->assertSame(['summary' => 'Factory summary.'], $function->run(['title' => 'Merino cardigan']));
        $this->assertCount(1, $provider->requests());
    }

    public function testFakeSdkFactoryKeepsComposerBaselineIndependentOfRuntimeServices(): void
    {
        $provider = FakeProvider::replying('{"summary":"Composer baseline."}');
        $sdk = Sdk::fake(
            provider: $provider,
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-composer-baseline-' . bin2hex(random_bytes(4)) . '.jsonl'),
        );

        $function = $sdk->smartFunction(
            name: 'catalog.summary',
            prompt: 'Summarize {{ title }} as JSON.',
            outputSchema: self::SUMMARY_SCHEMA,
        );

        $this->assertSame(['summary' => 'Composer baseline.'], $function->run(['title' => 'Merino cardigan']));
        $this->assertCount(1, $provider->requests());
    }

    public function testCreatesOpenAISdkFromProviderProfile(): void
    {
        $capturedHeaders = [];
        $sdk = Sdk::openAI(
            profile: ProviderProfile::openAI(model: 'gpt-test', secretName: 'PURPLE_OPENAI_KEY'),
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('sk-purple-test');
                }
            },
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-openai-' . bin2hex(random_bytes(4)) . '.jsonl'),
            transport: function (string $method, string $url, array $headers, array $payload) use (&$capturedHeaders): array {
                $capturedHeaders = $headers;

                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => '{"summary":"OpenAI factory summary."}',
                            ],
                        ],
                    ],
                ];
            },
        );

        $function = $sdk->smartFunction(
            name: 'catalog.summary',
            prompt: 'Summarize {{ title }} as JSON.',
            outputSchema: self::SUMMARY_SCHEMA,
        );

        $this->assertSame(['summary' => 'OpenAI factory summary.'], $function->run(['title' => 'Merino cardigan']));
        $this->assertSame('Bearer sk-purple-test', $capturedHeaders['Authorization'] ?? null);
    }

    public function testOpenAIFactoryRejectsWrongProviderProfile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OpenAI SDK factory requires provider profile "openai"');

        Sdk::openAI(profile: ProviderProfile::fake());
    }

    public function testCreatesAzureBedrockAndSidecarSdks(): void
    {
        $azure = Sdk::azureOpenAI(
            resource: 'purple-resource',
            profile: ProviderProfile::azureOpenAI(deployment: 'azure-deployment', secretName: 'AZURE_KEY'),
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('azure-secret');
                }
            },
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-azure-' . bin2hex(random_bytes(4)) . '.jsonl'),
            transport: static fn (): array => [
                'choices' => [
                    ['message' => ['content' => '{"summary":"Azure SDK."}']],
                ],
            ],
        );
        $bedrock = Sdk::bedrock(
            profile: ProviderProfile::bedrock(model: 'anthropic.model'),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-bedrock-' . bin2hex(random_bytes(4)) . '.jsonl'),
            transport: static fn (): array => [
                'output' => [
                    'message' => [
                        'content' => [
                            ['text' => '{"summary":"Bedrock SDK."}'],
                        ],
                    ],
                ],
            ],
        );
        $sidecar = Sdk::sidecar(
            endpoint: 'http://localhost:8787',
            profile: ProviderProfile::sidecar(model: 'brokered-model'),
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('sidecar-secret');
                }
            },
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-sdk-sidecar-' . bin2hex(random_bytes(4)) . '.jsonl'),
            transport: static fn (): array => [
                'content' => '{"summary":"Sidecar SDK."}',
            ],
        );

        $azureFunction = $azure->smartFunction('catalog.summary', 'Summarize {{ title }}.', self::SUMMARY_SCHEMA);
        $bedrockFunction = $bedrock->smartFunction('catalog.summary', 'Summarize {{ title }}.', self::SUMMARY_SCHEMA);
        $sidecarFunction = $sidecar->smartFunction('catalog.summary', 'Summarize {{ title }}.', self::SUMMARY_SCHEMA);

        $this->assertSame(['summary' => 'Azure SDK.'], $azureFunction->run(['title' => 'Hat']));
        $this->assertSame(['summary' => 'Bedrock SDK.'], $bedrockFunction->run(['title' => 'Hat']));
        $this->assertSame(['summary' => 'Sidecar SDK.'], $sidecarFunction->run(['title' => 'Hat']));
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
