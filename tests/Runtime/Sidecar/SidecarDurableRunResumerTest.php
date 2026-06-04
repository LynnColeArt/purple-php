<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\Sidecar;

use DateTimeImmutable;
use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Runtime\RuntimeException;
use Purple\Runtime\Sidecar\CallbackSidecarTransport;
use Purple\Runtime\Sidecar\SidecarEnvelope;
use Purple\Runtime\Sidecar\SidecarDurableRunResumer;
use Purple\Runtime\Sidecar\SidecarResumeClient;
use Purple\Runtime\Sidecar\SidecarResumeRequest;
use Purple\Runtime\Sidecar\SidecarResumeResponse;
use Purple\Tests\Testing\TestCase;

final class SidecarDurableRunResumerTest extends TestCase
{
    public function testResumesDurableRunThroughInjectableSidecarTransport(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-resume-' . bin2hex(random_bytes(4)));
        $store->save(new DurableRunRecord(
            runId: 'run-123',
            status: 'paused',
            state: [
                'step' => 2,
            ],
            updatedAt: new DateTimeImmutable('2026-06-04T12:00:00+00:00'),
        ));
        $capturedRequest = null;
        $transport = new CallbackSidecarTransport(function (SidecarEnvelope $envelope) use (&$capturedRequest): SidecarEnvelope {
            $capturedRequest = SidecarResumeRequest::fromEnvelope($envelope);

            return (new SidecarResumeResponse(
                runId: $capturedRequest->runId,
                status: 'accepted',
                message: 'Resume queued.',
                metadata: [
                    'sidecar_node' => 'local-dev',
                ],
            ))->toEnvelope();
        });

        $response = (new SidecarDurableRunResumer(
            runs: $store,
            client: new SidecarResumeClient($transport),
        ))->resume('run-123', metadata: ['requested_by' => 'test']);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('run-123', $capturedRequest->runId);
        $this->assertSame('continue', $capturedRequest->action);
        $this->assertSame('durable-run:run-123', $capturedRequest->statePointer);
        $this->assertSame('paused', $capturedRequest->status);
        $this->assertSame('test', $capturedRequest->metadata['requested_by'] ?? null);
        $this->assertSame('accepted', $response->status);
        $this->assertSame('Resume queued.', $response->message);
        $this->assertSame('local-dev', $response->metadata['sidecar_node'] ?? null);
    }

    public function testRejectsMissingDurableRun(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-resume-' . bin2hex(random_bytes(4)));
        $resumer = new SidecarDurableRunResumer(
            runs: $store,
            client: new SidecarResumeClient(new CallbackSidecarTransport(
                static fn (SidecarEnvelope $envelope): SidecarEnvelope => $envelope,
            )),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('was not found');

        $resumer->resume('missing-run');
    }

    public function testTransportCanRejectUnsupportedAction(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-sidecar-resume-' . bin2hex(random_bytes(4)));
        $store->save(new DurableRunRecord('run-123', 'paused'));
        $resumer = new SidecarDurableRunResumer(
            runs: $store,
            client: new SidecarResumeClient(new CallbackSidecarTransport(static function (SidecarEnvelope $envelope): SidecarEnvelope {
                $request = SidecarResumeRequest::fromEnvelope($envelope);

                if ($request->action !== SidecarResumeRequest::ACTION_CONTINUE) {
                    throw new RuntimeException('Unsupported sidecar resume action.');
                }

                return (new SidecarResumeResponse($request->runId, 'accepted'))->toEnvelope();
            })),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported sidecar resume action');

        $resumer->resume('run-123', action: 'rewind');
    }

    public function testRejectsMismatchedResponseRunId(): void
    {
        $client = new SidecarResumeClient(new CallbackSidecarTransport(
            static fn (): SidecarEnvelope => (new SidecarResumeResponse('other-run', 'accepted'))->toEnvelope(),
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('run ID did not match');

        $client->resume(new SidecarResumeRequest('run-123'));
    }

    public function testRejectsMalformedResumeResponseEnvelope(): void
    {
        $client = new SidecarResumeClient(new CallbackSidecarTransport(
            static fn (SidecarEnvelope $envelope): SidecarEnvelope => $envelope,
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected sidecar resume response envelope');

        $client->resume(new SidecarResumeRequest('run-123'));
    }
}
