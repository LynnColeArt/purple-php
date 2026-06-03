<?php

declare(strict_types=1);

namespace Purple\Domain;

use Purple\Tool\ToolSideEffectLevel;

final readonly class EnterpriseContext
{
    public function __construct(
        public string $tenantId,
        public string $userId,
        public DataSensitivity $dataSensitivity = DataSensitivity::Internal,
        public int $retentionDays = 30,
        public ?string $providerRoute = null,
    ) {
        if (trim($this->tenantId) === '' || trim($this->userId) === '') {
            throw new DomainException('Tenant and user context must not be empty.');
        }

        if ($this->retentionDays < 0) {
            throw new DomainException('Retention days must not be negative.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function policyMetadata(?ToolSideEffectLevel $sideEffectLevel = null): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'data_sensitivity' => $this->dataSensitivity->value,
            'retention_days' => $this->retentionDays,
            'provider_route' => $this->providerRoute,
            'side_effect_level' => $sideEffectLevel?->value,
        ];
    }
}
