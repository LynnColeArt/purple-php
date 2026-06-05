<?php

declare(strict_types=1);

namespace Purple\Runtime;

use Purple\Runtime\Sidecar\SidecarProtocol;

final readonly class NativeRuntimeReadiness
{
    /**
     * @return array<string, mixed>
     */
    public static function describe(): array
    {
        return [
            'native_runtime_required' => false,
            'composer_mode_baseline' => true,
            'extension_bridge' => [
                'contract' => 'Purple\\Contracts\\Runtime\\NativeRuntime',
                'default_extension' => 'purple_native',
                'status' => 'optional_bridge',
            ],
            'compatibility' => [
                'checker' => NativeRuntimeCompatibility::class,
                'operation' => NativeRuntimeCompatibility::OPERATION,
                'fixture_mode' => true,
                'extension_mode' => true,
                'status' => 'prototype',
            ],
            'sidecar_protocol' => [
                'version' => SidecarProtocol::VERSION,
                'encoding' => 'json_envelope',
                'status' => 'versioned_contract',
            ],
            'sandbox' => [
                'executor' => 'Purple\\Runtime\\Sandbox\\SandboxedToolExecutor',
                'default_allowed_side_effects' => ['none', 'read'],
                'enforces' => ['side_effect_level', 'payload_size', 'duration'],
            ],
            'durable_runs' => [
                'contract' => 'Purple\\Contracts\\Runtime\\DurableRunStore',
                'file_store' => 'Purple\\Runtime\\Durable\\FileDurableRunStore',
            ],
            'performance' => [
                'metrics' => ['duration_ms', 'memory_delta_bytes'],
            ],
            'on_prem' => [
                'sidecar_private_network_ready' => true,
                'native_extension_optional' => true,
                'cloud_dependency_required' => false,
            ],
        ];
    }
}
