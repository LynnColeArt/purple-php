<?php

declare(strict_types=1);

namespace Purple\Tests\Approval;

use Purple\Approval\ApprovalDecision;
use Purple\Approval\ApprovalRequest;
use Purple\Approval\StaticApprovalProvider;
use Purple\Tests\Testing\TestCase;

final class ApprovalTest extends TestCase
{
    public function testStaticApprovalProviderReturnsDecision(): void
    {
        $request = new ApprovalRequest(
            id: 'approval-1',
            runId: 'run-1',
            toolName: 'order.refund',
            reason: 'Tool requires approval.',
        );

        $approved = (new StaticApprovalProvider(true))->decide($request);
        $denied = (new StaticApprovalProvider(false, 'Not allowed.'))->decide($request);

        $this->assertTrue($approved->approved);
        $this->assertFalse($denied->approved);
        $this->assertSame('Not allowed.', $denied->reason);
        $this->assertSame(ApprovalDecision::approve()->approved, $approved->approved);
    }
}
