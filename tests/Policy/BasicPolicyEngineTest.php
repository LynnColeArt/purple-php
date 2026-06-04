<?php

declare(strict_types=1);

namespace Purple\Tests\Policy;

use Purple\Contracts\Policy\PolicyRequest;
use Purple\Policy\BasicPolicyEngine;
use Purple\Tests\Testing\TestCase;

final class BasicPolicyEngineTest extends TestCase
{
    public function testAllowsConfiguredProviderAndModel(): void
    {
        $decision = (new BasicPolicyEngine(
            allowedProviders: ['fake'],
            allowedModels: ['model-a'],
            maxRuns: 1,
        ))->decide(new PolicyRequest('smart_function.run', 'fake', 'model-a'));

        $this->assertTrue($decision->allowed);
    }

    public function testDeniesDisallowedProvider(): void
    {
        $decision = (new BasicPolicyEngine(allowedProviders: ['openai']))
            ->decide(new PolicyRequest('smart_function.run', 'fake', 'model-a'));

        $this->assertFalse($decision->allowed);
        $this->assertSame('Provider "fake" is not allowed.', $decision->reason);
    }

    public function testDeniesExhaustedRunBudget(): void
    {
        $policy = new BasicPolicyEngine(maxRuns: 1);

        $this->assertTrue($policy->decide(new PolicyRequest('smart_function.run', 'fake', 'model-a'))->allowed);

        $decision = $policy->decide(new PolicyRequest('smart_function.run', 'fake', 'model-a'));

        $this->assertFalse($decision->allowed);
        $this->assertSame('Run budget has been exhausted.', $decision->reason);
    }

    public function testDeniesDisallowedEnterpriseMetadata(): void
    {
        $policy = new BasicPolicyEngine(
            allowedTenantIds: ['tenant-a'],
            allowedProviderRoutes: ['private-model'],
            allowedDataResidencyRegions: ['us'],
        );

        $tenantDecision = $policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'tenant_id' => 'tenant-b',
                'provider_route' => 'private-model',
                'data_residency_region' => 'us',
            ],
        ));
        $routeDecision = $policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'tenant_id' => 'tenant-a',
                'provider_route' => 'public-model',
                'data_residency_region' => 'us',
            ],
        ));
        $residencyDecision = $policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'tenant_id' => 'tenant-a',
                'provider_route' => 'private-model',
                'data_residency_region' => 'eu',
            ],
        ));

        $this->assertFalse($tenantDecision->allowed);
        $this->assertSame('Tenant is not allowed by policy.', $tenantDecision->reason);
        $this->assertFalse($routeDecision->allowed);
        $this->assertSame('Provider route is not allowed by policy.', $routeDecision->reason);
        $this->assertFalse($residencyDecision->allowed);
        $this->assertSame('Data residency region is not allowed by policy.', $residencyDecision->reason);
    }
}
