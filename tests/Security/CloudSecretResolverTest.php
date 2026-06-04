<?php

declare(strict_types=1);

namespace Purple\Tests\Security;

use Purple\Security\CloudSecretResolver;
use Purple\Tests\Testing\TestCase;

final class CloudSecretResolverTest extends TestCase
{
    public function testResolvesSecretThroughBrokeredCloudEndpoint(): void
    {
        $captured = [];
        $resolver = new CloudSecretResolver(
            endpoint: 'https://secrets.internal',
            providerName: 'aws-secrets-manager',
            headers: ['Authorization' => 'Bearer broker-token'],
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return ['secret_string' => 'cloud-secret'];
            },
        );

        $secret = $resolver->resolve('OPENAI_API_KEY');

        $this->assertSame('cloud-secret', $secret->reveal());
        $this->assertSame('https://secrets.internal/secrets/resolve', $captured['url'] ?? null);
        $this->assertSame('aws-secrets-manager', $captured['payload']['provider'] ?? null);
        $this->assertSame('Bearer broker-token', $captured['headers']['Authorization'] ?? null);
    }
}
