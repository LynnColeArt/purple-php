<?php

declare(strict_types=1);

namespace Purple\Contracts\Security;

use Purple\Domain\EnterpriseContext;

interface EnterpriseSecretResolver
{
    public function resolveForContext(string $name, EnterpriseContext $context): SecretValue;
}
