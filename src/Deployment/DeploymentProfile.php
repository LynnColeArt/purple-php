<?php

declare(strict_types=1);

namespace Purple\Deployment;

final readonly class DeploymentProfile
{
    /**
     * @param list<string> $capabilities
     * @param list<string> $requirements
     */
    public function __construct(
        public DeploymentMode $mode,
        public string $label,
        public bool $nativeRuntimeRequired,
        public array $capabilities,
        public array $requirements = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return [
            'mode' => $this->mode->value,
            'label' => $this->label,
            'native_runtime_required' => $this->nativeRuntimeRequired,
            'capabilities' => $this->capabilities,
            'requirements' => $this->requirements,
        ];
    }
}
