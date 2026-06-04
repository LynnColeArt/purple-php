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
        public ?string $dataResidencyRegion = null,
    ) {
        if (trim($this->tenantId) === '' || trim($this->userId) === '') {
            throw new DomainException('Tenant and user context must not be empty.');
        }

        if ($this->providerRoute !== null && trim($this->providerRoute) === '') {
            throw new DomainException('Provider route must not be empty when configured.');
        }

        if ($this->dataResidencyRegion !== null && trim($this->dataResidencyRegion) === '') {
            throw new DomainException('Data residency region must not be empty when configured.');
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
            'data_residency_region' => $this->dataResidencyRegion,
            'side_effect_level' => $sideEffectLevel?->value,
        ];
    }
}
