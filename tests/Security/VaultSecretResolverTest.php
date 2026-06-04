<?php

declare(strict_types=1);

namespace Purple\Tests\Security;

use Purple\Contracts\Security\SecretValue;
use Purple\Security\VaultSecretResolver;
use Purple\Tests\Testing\TestCase;

final class VaultSecretResolverTest extends TestCase
{
    public function testResolvesKvV2SecretValue(): void
    {
        $captured = [];
        $resolver = new VaultSecretResolver(
            endpoint: 'https://vault.internal',
            token: SecretValue::fromString('vault-token'),
            transport: function (string $method, string $url, array $headers) use (&$captured): array {
                $captured = compact('method', 'url', 'headers');

                return [
                    'data' => [
                        'data' => [
                            'value' => 'resolved-secret',
                        ],
                    ],
                ];
            },
        );

        $secret = $resolver->resolve('openai/api-key');

        $this->assertSame('resolved-secret', $secret->reveal());
        $this->assertSame('https://vault.internal/v1/secret/data/openai/api-key', $captured['url'] ?? null);
        $this->assertSame('vault-token', $captured['headers']['X-Vault-Token'] ?? null);
    }
}
