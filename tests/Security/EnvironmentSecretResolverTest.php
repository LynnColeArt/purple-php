<?php

declare(strict_types=1);

namespace Purple\Tests\Security;

use InvalidArgumentException;
use Purple\Security\EnvironmentSecretResolver;
use Purple\Tests\Testing\TestCase;

final class EnvironmentSecretResolverTest extends TestCase
{
    public function testResolvesEnvironmentSecret(): void
    {
        putenv('PURPLE_TEST_SECRET=sk-test-value');

        $secret = (new EnvironmentSecretResolver())->resolve('PURPLE_TEST_SECRET');

        $this->assertSame('sk-test-value', $secret->reveal());
        $this->assertSame('*********alue', $secret->redacted());
    }

    public function testRejectsMissingEnvironmentSecret(): void
    {
        putenv('PURPLE_MISSING_SECRET');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not set');

        (new EnvironmentSecretResolver())->resolve('PURPLE_MISSING_SECRET');
    }
}
