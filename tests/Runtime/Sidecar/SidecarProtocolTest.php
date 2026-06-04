<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\Sidecar;

use Purple\Runtime\RuntimeException;
use Purple\Runtime\Sidecar\SidecarEnvelope;
use Purple\Runtime\Sidecar\SidecarProtocol;
use Purple\Tests\Testing\TestCase;

final class SidecarProtocolTest extends TestCase
{
    public function testEncodesAndDecodesVersionedSidecarEnvelope(): void
    {
        $protocol = new SidecarProtocol();
        $envelope = new SidecarEnvelope(
            version: SidecarProtocol::VERSION,
            type: 'tool.invoke',
            runId: 'run-123',
            payload: [
                'tool' => 'catalog.lookup',
            ],
        );

        $decoded = $protocol->decode($protocol->encode($envelope));

        $this->assertSame(SidecarProtocol::VERSION, $decoded->version);
        $this->assertSame('tool.invoke', $decoded->type);
        $this->assertSame('run-123', $decoded->runId);
        $this->assertSame('catalog.lookup', $decoded->payload['tool'] ?? null);
    }

    public function testRejectsUnsupportedSidecarEnvelopeVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported sidecar envelope version');

        (new SidecarProtocol())->decode('{"version":"purple.sidecar.v0","type":"ping","run_id":"run-123","payload":{}}');
    }
}
