<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Security;

use InvalidArgumentException;
use Purple\Contracts\Security\SecretValue;
use Purple\Tests\Testing\TestCase;

final class SecretValueTest extends TestCase
{
    public function testRedactsSecretByDefault(): void
    {
        $secret = SecretValue::fromString('sk-purple-secret-value');

        $this->assertSame('sk-purple-secret-value', $secret->reveal());
        $this->assertSame('******************alue', $secret->redacted());
        $this->assertSame($secret->redacted(), (string) $secret);
    }

    public function testFullyRedactsShortSecrets(): void
    {
        $secret = SecretValue::fromString('short');

        $this->assertSame('********', $secret->redacted());
    }

    public function testRejectsEmptySecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        SecretValue::fromString('');
    }
}
