<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Audit;

use Purple\Contracts\Audit\AuditEvent;
use Purple\Tests\Testing\TestCase;

final class AuditEventTest extends TestCase
{
    public function testCreatesAuditEventForCurrentTime(): void
    {
        $event = AuditEvent::now('provider.requested', 'run-123', [
            'provider' => 'fake',
        ]);

        $this->assertSame('provider.requested', $event->type);
        $this->assertSame('run-123', $event->runId);
        $this->assertSame('fake', $event->metadata['provider']);
    }
}
