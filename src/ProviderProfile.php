<?php

declare(strict_types=1);

namespace Purple;

use InvalidArgumentException;
use Purple\Policy\BasicPolicyEngine;

final readonly class ProviderProfile
{
    /** @var list<string> */
    public array $allowedProviders;

    /** @var list<string> */
    public array $allowedModels;

    /**
     * @param list<string> $allowedProviders
     * @param list<string> $allowedModels
     * @param list<string> $allowedTenantIds
     * @param list<string> $allowedProviderRoutes
     * @param list<string> $allowedDataResidencyRegions
     */
    public function __construct(
        public string $providerName,
        public string $model,
        public ?string $secretName = null,
        public ?string $auditPath = null,
        array $allowedProviders = [],
        array $allowedModels = [],
        public ?int $maxRuns = null,
        public ?float $maxEstimatedCostUsd = null,
        public array $allowedTenantIds = [],
        public array $allowedProviderRoutes = [],
        public array $allowedDataResidencyRegions = [],
    ) {
        $this->assertNonEmpty($this->providerName, 'Provider name');
        $this->assertNonEmpty($this->model, 'Model');

        if ($this->secretName !== null) {
            $this->assertNonEmpty($this->secretName, 'Secret name');
        }

        if ($this->auditPath !== null) {
            $this->assertNonEmpty($this->auditPath, 'Audit path');
        }

        if ($this->maxRuns !== null && $this->maxRuns < 1) {
            throw new InvalidArgumentException('Max runs must be at least 1 when configured.');
        }

        $this->allowedProviders = $allowedProviders === []
            ? [$this->providerName]
            : $this->validateStringList($allowedProviders, 'Allowed providers');
        $this->allowedModels = $allowedModels === []
            ? [$this->model]
            : $this->validateStringList($allowedModels, 'Allowed models');
        $this->validateStringList($this->allowedTenantIds, 'Allowed tenant IDs');
        $this->validateStringList($this->allowedProviderRoutes, 'Allowed provider routes');
        $this->validateStringList($this->allowedDataResidencyRegions, 'Allowed data residency regions');
    }

    public static function fake(string $model = 'fake-model', ?string $auditPath = null): self
    {
        return new self(
            providerName: 'fake',
            model: $model,
            auditPath: $auditPath,
        );
    }

    public static function openAI(
        string $model = 'gpt-4.1-mini',
        string $secretName = 'OPENAI_API_KEY',
        ?string $auditPath = null,
    ): self {
        return new self(
            providerName: 'openai',
            model: $model,
            secretName: $secretName,
            auditPath: $auditPath,
        );
    }

    public static function azureOpenAI(
        string $deployment = 'gpt-4.1-mini',
        string $secretName = 'AZURE_OPENAI_API_KEY',
        ?string $auditPath = null,
    ): self {
        return new self(
            providerName: 'azure_openai',
            model: $deployment,
            secretName: $secretName,
            auditPath: $auditPath,
        );
    }

    public static function bedrock(
        string $model = 'anthropic.claude-3-haiku-20240307-v1:0',
        ?string $auditPath = null,
    ): self {
        return new self(
            providerName: 'bedrock',
            model: $model,
            auditPath: $auditPath,
        );
    }

    public static function sidecar(
        string $model = 'brokered-model',
        string $secretName = 'PURPLE_SIDECAR_TOKEN',
        ?string $auditPath = null,
    ): self {
        return new self(
            providerName: 'sidecar',
            model: $model,
            secretName: $secretName,
            auditPath: $auditPath,
        );
    }

    public function policy(): BasicPolicyEngine
    {
        return new BasicPolicyEngine(
            allowedProviders: $this->allowedProviders,
            allowedModels: $this->allowedModels,
            maxRuns: $this->maxRuns,
            maxEstimatedCostUsd: $this->maxEstimatedCostUsd,
            allowedTenantIds: $this->allowedTenantIds,
            allowedProviderRoutes: $this->allowedProviderRoutes,
            allowedDataResidencyRegions: $this->allowedDataResidencyRegions,
        );
    }

    private function assertNonEmpty(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($label . ' must not be empty.');
        }
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function validateStringList(array $values, string $label): array
    {
        foreach ($values as $value) {
            $this->assertNonEmpty($value, $label . ' entry');
        }

        return $values;
    }
}
