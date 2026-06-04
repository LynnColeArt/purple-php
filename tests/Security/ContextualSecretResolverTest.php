<?php

declare(strict_types=1);

namespace Purple\Tests\Security;

use Purple\Domain\EnterpriseContext;
use Purple\Security\ContextualSecretResolver;
use Purple\Security\EnvironmentSecretResolver;
use Purple\Tests\Testing\TestCase;

final class ContextualSecretResolverTest extends TestCase
{
    public function testPrefersTenantSpecificSecretWhenConfigured(): void
    {
        putenv('TENANT_A_OPENAI_API_KEY=tenant-secret');
        putenv('OPENAI_API_KEY=default-secret');

        try {
            $resolver = new ContextualSecretResolver(new EnvironmentSecretResolver());
            $secret = $resolver->resolveForContext('OPENAI_API_KEY', new EnterpriseContext('tenant-a', 'user-42'));

            $this->assertSame('tenant-secret', $secret->reveal());
        } finally {
            putenv('TENANT_A_OPENAI_API_KEY');
            putenv('OPENAI_API_KEY');
        }
    }
}
