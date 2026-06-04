<?php

declare(strict_types=1);

namespace Purple\Tests\Provider\Azure;

use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\Provider\Azure\AzureOpenAIProvider;
use Purple\Tests\Testing\TestCase;

final class AzureOpenAIProviderTest extends TestCase
{
    public function testBuildsAzureOpenAIRequestAndParsesResponse(): void
    {
        $captured = [];
        $provider = new AzureOpenAIProvider(
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('azure-secret');
                }
            },
            resource: 'purple-resource',
            deployment: 'gpt-deployment',
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return [
                    'choices' => [
                        ['message' => ['content' => '{"summary":"Azure answer."}']],
                    ],
                    'usage' => [
                        'prompt_tokens' => 3,
                        'completion_tokens' => 2,
                    ],
                ];
            },
        );

        $response = $provider->complete(ProviderRequest::fromPrompt('ignored-by-azure', 'Hello.'));

        $this->assertSame('{"summary":"Azure answer."}', $response->content);
        $this->assertSame(5, $response->usage?->totalTokens());
        $url = $captured['url'] ?? null;
        $this->assertIsString($url);
        $this->assertStringContainsString('purple-resource.openai.azure.com', $url);
        $this->assertStringContainsString('/deployments/gpt-deployment/', $url);
        $this->assertSame('azure-secret', $captured['headers']['api-key'] ?? null);
    }
}
