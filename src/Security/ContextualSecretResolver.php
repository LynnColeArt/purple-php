<?php

declare(strict_types=1);

namespace Purple\Security;

use Purple\Contracts\Security\EnterpriseSecretResolver;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\Domain\EnterpriseContext;

final readonly class ContextualSecretResolver implements EnterpriseSecretResolver
{
    public function __construct(private SecretResolver $inner)
    {
    }

    public function resolveForContext(string $name, EnterpriseContext $context): SecretValue
    {
        return $this->inner->resolve($this->contextualName($name, $context));
    }

    private function contextualName(string $name, EnterpriseContext $context): string
    {
        $tenantName = sprintf('%s_%s', strtoupper(str_replace('-', '_', $context->tenantId)), $name);

        if (getenv($tenantName) !== false) {
            return $tenantName;
        }

        return $name;
    }
}
