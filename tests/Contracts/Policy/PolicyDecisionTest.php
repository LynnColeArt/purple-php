<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Policy;

use InvalidArgumentException;
use Purple\Contracts\Policy\PolicyDecision;
use Purple\Tests\Testing\TestCase;

final class PolicyDecisionTest extends TestCase
{
    public function testAllowsDecision(): void
    {
        $decision = PolicyDecision::allow('safe read-only operation');

        $this->assertTrue($decision->allowed);
        $this->assertSame('safe read-only operation', $decision->reason);
    }

    public function testDeniedDecisionRequiresReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must include a reason');

        PolicyDecision::deny('');
    }
}
