<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime;

use Purple\Runtime\NativeRuntimeReadiness;
use Purple\Runtime\Sidecar\SidecarProtocol;
use Purple\Tests\Testing\TestCase;

final class NativeRuntimeReadinessTest extends TestCase
{
    public function testDescribesOptionalNativeRuntimeReadiness(): void
    {
        $description = NativeRuntimeReadiness::describe();
        $extensionBridge = $description['extension_bridge'] ?? null;
        $sidecarProtocol = $description['sidecar_protocol'] ?? null;
        $sandbox = $description['sandbox'] ?? null;
        $onPrem = $description['on_prem'] ?? null;

        $this->assertFalse($description['native_runtime_required'] ?? true);
        $this->assertTrue($description['composer_mode_baseline'] ?? false);
        $this->assertIsArray($extensionBridge);
        $this->assertIsArray($sidecarProtocol);
        $this->assertIsArray($sandbox);
        $this->assertIsArray($onPrem);
        $this->assertSame('optional_bridge', $extensionBridge['status'] ?? null);
        $this->assertSame(SidecarProtocol::VERSION, $sidecarProtocol['version'] ?? null);
        $this->assertSame(['none', 'read'], $sandbox['default_allowed_side_effects'] ?? null);
        $this->assertFalse($onPrem['cloud_dependency_required'] ?? true);
    }
}
