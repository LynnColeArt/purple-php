<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\Sidecar;

use DateTimeImmutable;
use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Runtime\RuntimeException;
use Purple\Runtime\Sidecar\SidecarProtocol;
use Purple\Runtime\Sidecar\SidecarResumeRequest;
use Purple\Runtime\Sidecar\SidecarResumeResponse;
use Purple\Runtime\Sidecar\SidecarRuntimeService;
use Purple\Tests\Testing\TestCase;

final class SidecarRuntimeServiceTest extends TestCase
{
    public function testHandlesEncodedResumeRequestForExistingDurableRun(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-service-' . bin2hex(random_bytes(4)));
        $store->save(new DurableRunRecord(
            runId: 'run-123',
            status: 'paused',
            state: ['step' => 2],
            updatedAt: new DateTimeImmutable('2026-06-05T01:00:00+00:00'),
        ));
        $protocol = new SidecarProtocol();
        $request = new SidecarResumeRequest(
            runId: 'run-123',
            action: SidecarResumeRequest::ACTION_CONTINUE,
            statePointer: 'durable-run:run-123',
            status: 'paused',
            metadata: ['requested_by' => 'test'],
        );

        $rawResponse = (new SidecarRuntimeService($store, 'test-sidecar', $protocol))
            ->handle($protocol->encode($request->toEnvelope()));
        $response = SidecarResumeResponse::fromEnvelope($protocol->decode($rawResponse));

        $this->assertSame('run-123', $response->runId);
        $this->assertSame('accepted', $response->status);
        $this->assertSame('Resume request accepted by local sidecar runtime.', $response->message);
        $this->assertSame('test-sidecar', $response->metadata['sidecar_node'] ?? null);
        $this->assertSame('continue', $response->metadata['action'] ?? null);
        $this->assertSame('durable-run:run-123', $response->metadata['state_pointer'] ?? null);
        $this->assertSame('accepted', $response->metadata['reason'] ?? null);
        $this->assertSame('paused', $response->metadata['record_status'] ?? null);
        $this->assertSame('2026-06-05T01:00:00+00:00', $response->metadata['record_updated_at'] ?? null);
    }

    public function testRejectsMissingDurableRun(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-service-' . bin2hex(random_bytes(4)));
        $response = (new SidecarRuntimeService($store, 'test-sidecar'))
            ->resume(new SidecarResumeRequest('missing-run'));

        $this->assertSame('missing-run', $response->runId);
        $this->assertSame('rejected', $response->status);
        $this->assertSame('Durable run was not found for sidecar resume.', $response->message);
        $this->assertSame('missing_run', $response->metadata['reason'] ?? null);
        $this->assertSame('test-sidecar', $response->metadata['sidecar_node'] ?? null);
    }

    public function testRejectsUnsupportedResumeAction(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-service-' . bin2hex(random_bytes(4)));
        $store->save(new DurableRunRecord('run-123', 'paused'));
        $response = (new SidecarRuntimeService($store, 'test-sidecar'))
            ->resume(new SidecarResumeRequest('run-123', action: 'rewind'));

        $this->assertSame('run-123', $response->runId);
        $this->assertSame('rejected', $response->status);
        $this->assertSame('Unsupported sidecar resume action.', $response->message);
        $this->assertSame('unsupported_action', $response->metadata['reason'] ?? null);
        $this->assertSame('rewind', $response->metadata['action'] ?? null);
    }

    public function testMalformedEnvelopeFailsLoudly(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-service-' . bin2hex(random_bytes(4)));
        $service = new SidecarRuntimeService($store);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to decode sidecar envelope');

        $service->handle('{not-json');
    }

    public function testRejectsEmptyNodeId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('node ID must not be empty');

        new SidecarRuntimeService(
            new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-service-' . bin2hex(random_bytes(4))),
            '   ',
        );
    }
}
