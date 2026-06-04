<?php

declare(strict_types=1);

namespace Purple\Deployment;

final readonly class DeploymentReadiness
{
    /**
     * @return list<DeploymentProfile>
     */
    public static function profiles(): array
    {
        return [
            new DeploymentProfile(
                mode: DeploymentMode::Composer,
                label: 'Pure Composer SDK',
                nativeRuntimeRequired: false,
                capabilities: ['smart_functions', 'chat', 'agents', 'enterprise_policy', 'redaction', 'cloud_providers', 'audit_export'],
                requirements: ['PHP 8.2+', 'Composer autoload'],
            ),
            new DeploymentProfile(
                mode: DeploymentMode::Sidecar,
                label: 'Optional sidecar runtime',
                nativeRuntimeRequired: false,
                capabilities: ['provider_brokerage', 'observability_export', 'central_policy_coordination', 'secret_brokerage', 'sidecar_protocol', 'sandboxed_tool_execution', 'durable_agent_runs', 'runtime_metrics', 'on_prem_readiness'],
                requirements: ['Network path from PHP application to sidecar service', 'Versioned Purple sidecar protocol support'],
            ),
            new DeploymentProfile(
                mode: DeploymentMode::NativeExtension,
                label: 'Optional native extension',
                nativeRuntimeRequired: false,
                capabilities: ['low_latency_boundary', 'native_secret_brokerage', 'runtime_attestation', 'php_extension_bridge', 'sandboxed_tool_execution', 'durable_agent_runs', 'runtime_metrics', 'on_prem_readiness'],
                requirements: ['Explicit installation by platform team', 'Optional purple_native extension or compatible invoker'],
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function describe(): array
    {
        return array_map(
            static fn (DeploymentProfile $profile): array => $profile->describe(),
            self::profiles(),
        );
    }
}
