<?php

declare(strict_types=1);

namespace Purple\Tests\Provider\OpenAI;

use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Provider\OpenAI\OpenAIProvider;
use Purple\Tests\Testing\TestCase;

final class OpenAIProviderTest extends TestCase
{
    public function testBuildsOpenAIRequestWithSecretResolverAndParsesResponse(): void
    {
        $captured = [
            'method' => '',
            'url' => '',
            'headers' => [],
            'payload' => [],
        ];
        $provider = new OpenAIProvider(
            new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('sk-openai-test');
                }
            },
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => '{"title":"A good answer"}',
                            ],
                        ],
                    ],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 4,
                    ],
                ];
            },
        );

        $response = $provider->complete(ProviderRequest::fromPrompt('gpt-test', 'Write a title.'));
        $payload = $captured['payload'];
        $messages = $payload['messages'] ?? null;

        $this->assertSame('{"title":"A good answer"}', $response->content);
        $this->assertSame(14, $response->usage?->totalTokens());
        $this->assertIsArray($messages);
        $this->assertIsArray($messages[0]);
        $this->assertSame('POST', $captured['method']);
        $this->assertSame('https://api.openai.com/v1/chat/completions', $captured['url']);
        $this->assertSame('Bearer sk-openai-test', $captured['headers']['Authorization']);
        $this->assertSame('gpt-test', $payload['model']);
        $this->assertSame('Write a title.', $messages[0]['content']);
    }
}
