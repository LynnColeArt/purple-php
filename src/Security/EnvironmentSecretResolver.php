<?php

declare(strict_types=1);

namespace Purple\Security;

use InvalidArgumentException;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;

final readonly class EnvironmentSecretResolver implements SecretResolver
{
    public function resolve(string $name): SecretValue
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Environment secret name must not be empty.');
        }

        $value = getenv($name);

        if ($value === false || $value === '') {
            throw new InvalidArgumentException(sprintf('Environment secret "%s" is not set.', $name));
        }

        return SecretValue::fromString($value);
    }
}
