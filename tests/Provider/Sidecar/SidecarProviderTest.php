<?php

declare(strict_types=1);

namespace Purple\Tests\Provider\Sidecar;

use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\Provider\Sidecar\SidecarProvider;
use Purple\Tests\Testing\TestCase;

final class SidecarProviderTest extends TestCase
{
    public function testBuildsSidecarRequestAndParsesResponse(): void
    {
        $captured = [];
        $provider = new SidecarProvider(
            endpoint: 'http://localhost:8787',
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('sidecar-token');
                }
            },
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return [
                    'content' => '{"summary":"Sidecar answer."}',
                    'metadata' => [
                        'route' => 'private-model',
                    ],
                    'usage' => [
                        'input_tokens' => 6,
                        'output_tokens' => 7,
                        'cost_usd' => 0.01,
                    ],
                ];
            },
        );

        $response = $provider->complete(ProviderRequest::fromPrompt('brokered-model', 'Hello sidecar.'));

        $this->assertSame('{"summary":"Sidecar answer."}', $response->content);
        $this->assertSame(13, $response->usage?->totalTokens());
        $this->assertNotNull($response->usage);
        $this->assertSame(0.01, $response->usage->costUsd);
        $this->assertSame('http://localhost:8787/v1/provider/complete', $captured['url'] ?? null);
        $this->assertSame('Bearer sidecar-token', $captured['headers']['Authorization'] ?? null);
        $this->assertSame('brokered-model', $captured['payload']['model'] ?? null);
    }
}
