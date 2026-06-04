<?php

declare(strict_types=1);

namespace Purple\Tests\Policy;

use Purple\Contracts\Policy\PolicyRequest;
use Purple\Policy\BasicPolicyEngine;
use Purple\Policy\EnterprisePolicyEngine;
use Purple\Policy\Rules\RestrictedDataRouteRule;
use Purple\Policy\Rules\RetentionLimitRule;
use Purple\Policy\Rules\SideEffectApprovalRule;
use Purple\Tests\Testing\TestCase;

final class EnterprisePolicyEngineTest extends TestCase
{
    public function testComposesBasePolicyAndEnterpriseRules(): void
    {
        $policy = new EnterprisePolicyEngine(
            new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.update'], allowedModels: ['fake-model', 'write']),
            [
                new RestrictedDataRouteRule(['private-model']),
                new RetentionLimitRule(30),
                new SideEffectApprovalRule(),
            ],
        );

        $restrictedDecision = $policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'data_sensitivity' => 'restricted',
                'provider_route' => 'public-model',
            ],
        ));
        $retentionDecision = $policy->decide(new PolicyRequest(
            operation: 'chat.send',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'retention_days' => 90,
            ],
        ));
        $sideEffectDecision = $policy->decide(new PolicyRequest(
            operation: 'agent.tool_call',
            provider: 'catalog.update',
            model: 'write',
            metadata: [
                'side_effect_level' => 'write',
            ],
        ));
        $allowedDecision = $policy->decide(new PolicyRequest(
            operation: 'agent.tool_call',
            provider: 'fake',
            model: 'fake-model',
            metadata: [
                'data_sensitivity' => 'restricted',
                'provider_route' => 'private-model',
                'retention_days' => 7,
                'side_effect_level' => 'write',
                'approval_granted' => true,
            ],
        ));

        $this->assertFalse($restrictedDecision->allowed);
        $this->assertSame('Restricted data requires an approved provider route.', $restrictedDecision->reason);
        $this->assertFalse($retentionDecision->allowed);
        $this->assertSame('Retention period exceeds policy maximum.', $retentionDecision->reason);
        $this->assertFalse($sideEffectDecision->allowed);
        $this->assertSame('Tool side effect requires explicit approval metadata.', $sideEffectDecision->reason);
        $this->assertTrue($allowedDecision->allowed);
    }
}
