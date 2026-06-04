<?php

declare(strict_types=1);

use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Runtime\Sidecar\CallbackSidecarTransport;
use Purple\Runtime\Sidecar\SidecarEnvelope;
use Purple\Runtime\Sidecar\SidecarDurableRunResumer;
use Purple\Runtime\Sidecar\SidecarResumeClient;
use Purple\Runtime\Sidecar\SidecarResumeRequest;
use Purple\Runtime\Sidecar\SidecarResumeResponse;

require __DIR__ . '/../../vendor/autoload.php';

$store = new FileDurableRunStore(__DIR__ . '/../../var/runtime/runs');
$store->save(new DurableRunRecord(
    runId: 'run-resume-example',
    status: 'paused',
    state: [
        'agent' => 'catalog.agent',
        'step' => 2,
        'tool_calls' => 1,
    ],
));

$transport = new CallbackSidecarTransport(static function (SidecarEnvelope $envelope): SidecarEnvelope {
    $request = SidecarResumeRequest::fromEnvelope($envelope);

    return (new SidecarResumeResponse(
        runId: $request->runId,
        status: 'accepted',
        message: 'Resume request accepted by fake sidecar transport.',
        metadata: [
            'action' => $request->action,
            'state_pointer' => $request->statePointer,
            'sidecar_node' => 'local-dev',
        ],
    ))->toEnvelope();
});

$response = (new SidecarDurableRunResumer(
    runs: $store,
    client: new SidecarResumeClient($transport),
))->resume('run-resume-example', metadata: [
    'requested_by' => 'composer-example',
]);

print json_encode([
    'run_id' => $response->runId,
    'status' => $response->status,
    'message' => $response->message,
    'metadata' => $response->metadata,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
