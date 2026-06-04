<?php

declare(strict_types=1);

namespace Purple\Tests\Deployment;

use Purple\Deployment\DeploymentMode;
use Purple\Deployment\DeploymentReadiness;
use Purple\Tests\Testing\TestCase;

final class DeploymentReadinessTest extends TestCase
{
    public function testDeploymentProfilesKeepNativeRuntimeOptional(): void
    {
        $profiles = DeploymentReadiness::profiles();
        $descriptions = DeploymentReadiness::describe();

        $this->assertCount(3, $profiles);
        $this->assertSame(DeploymentMode::Composer, $profiles[0]->mode);
        $this->assertFalse($profiles[0]->nativeRuntimeRequired);
        $this->assertFalse($profiles[1]->nativeRuntimeRequired);
        $this->assertFalse($profiles[2]->nativeRuntimeRequired);
        $this->assertContains('cloud_providers', $profiles[0]->capabilities);
        $this->assertContains('secret_brokerage', $profiles[1]->capabilities);
        $this->assertContains('sidecar_protocol', $profiles[1]->capabilities);
        $this->assertContains('durable_agent_runs', $profiles[1]->capabilities);
        $this->assertContains('php_extension_bridge', $profiles[2]->capabilities);
        $this->assertContains('runtime_metrics', $profiles[2]->capabilities);
        $this->assertSame('composer', $descriptions[0]['mode']);
        $this->assertSame('sidecar', $descriptions[1]['mode']);
        $this->assertSame('native_extension', $descriptions[2]['mode']);
    }

    public function testComposerProfileHasNoRuntimeInfrastructureRequirement(): void
    {
        $composer = DeploymentReadiness::profiles()[0];

        $this->assertSame(DeploymentMode::Composer, $composer->mode);
        $this->assertSame(['PHP 8.2+', 'Composer autoload'], $composer->requirements);
        $this->assertNotContains('sidecar_protocol', $composer->capabilities);
        $this->assertNotContains('durable_agent_runs', $composer->capabilities);
        $this->assertNotContains('php_extension_bridge', $composer->capabilities);
        $this->assertNotContains('runtime_metrics', $composer->capabilities);
    }

    public function testRuntimeGeneratedPathsStayIgnored(): void
    {
        $ignore = file_get_contents(__DIR__ . '/../../.gitignore');

        $this->assertIsString($ignore);
        $this->assertStringContainsString('var/audit/', $ignore);
        $this->assertStringContainsString('var/runtime/', $ignore);
    }
}
