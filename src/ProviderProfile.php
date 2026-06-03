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

    public function policy(): BasicPolicyEngine
    {
        return new BasicPolicyEngine(
            allowedProviders: $this->allowedProviders,
            allowedModels: $this->allowedModels,
            maxRuns: $this->maxRuns,
            maxEstimatedCostUsd: $this->maxEstimatedCostUsd,
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
