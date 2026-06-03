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
        $this->assertSame('composer', $descriptions[0]['mode']);
        $this->assertSame('sidecar', $descriptions[1]['mode']);
        $this->assertSame('native_extension', $descriptions[2]['mode']);
    }
}
