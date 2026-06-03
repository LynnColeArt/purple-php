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
}
