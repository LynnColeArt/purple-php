<?php

declare(strict_types=1);

namespace Purple\Tests\Provider\Bedrock;

use Purple\Contracts\Provider\ProviderRequest;
use Purple\Provider\Bedrock\BedrockProvider;
use Purple\Tests\Testing\TestCase;

final class BedrockProviderTest extends TestCase
{
    public function testBuildsBedrockConverseRequestAndParsesResponse(): void
    {
        $captured = [];
        $provider = new BedrockProvider(
            region: 'us-west-2',
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return [
                    'output' => [
                        'message' => [
                            'content' => [
                                ['text' => '{"summary":"Bedrock answer."}'],
                            ],
                        ],
                    ],
                    'usage' => [
                        'inputTokens' => 4,
                        'outputTokens' => 5,
                    ],
                ];
            },
        );

        $response = $provider->complete(ProviderRequest::fromPrompt('anthropic.model', 'Hello Bedrock.'));

        $this->assertSame('{"summary":"Bedrock answer."}', $response->content);
        $this->assertSame(9, $response->usage?->totalTokens());
        $url = $captured['url'] ?? null;
        $this->assertIsString($url);
        $this->assertStringContainsString('bedrock-runtime.us-west-2.amazonaws.com', $url);
        $this->assertStringContainsString('/model/anthropic.model/converse', $url);
        $this->assertSame('application/json', $captured['headers']['Content-Type'] ?? null);
    }
}
