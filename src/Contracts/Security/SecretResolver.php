<?php

declare(strict_types=1);

namespace Purple\Contracts\Security;

interface SecretResolver
{
    public function resolve(string $name): SecretValue;
}
