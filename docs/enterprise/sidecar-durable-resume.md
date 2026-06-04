# Sidecar Durable Resume Contract

Purple PHP can hand durable agent run state to a sidecar without requiring a sidecar process during Composer-mode validation.

The Phase 5.1 durable resume contract uses `SidecarProtocol::VERSION` and two envelope types:

- `agent.run.resume.request`
- `agent.run.resume.response`

## Request Shape

```php
[
    'version' => 'purple.sidecar.v1',
    'type' => 'agent.run.resume.request',
    'run_id' => 'run-123',
    'payload' => [
        'action' => 'continue',
        'state_pointer' => 'durable-run:run-123',
        'status' => 'paused',
        'metadata' => [
            'requested_by' => 'operator',
        ],
    ],
]
```

The request is represented by `SidecarResumeRequest`. `SidecarResumeRequest::fromRecord()` builds a request from a `DurableRunRecord` without coupling the sidecar contract to a specific durable store implementation.

## Response Shape

```php
[
    'version' => 'purple.sidecar.v1',
    'type' => 'agent.run.resume.response',
    'run_id' => 'run-123',
    'payload' => [
        'status' => 'accepted',
        'message' => 'Resume queued.',
        'metadata' => [
            'sidecar_node' => 'local-dev',
        ],
    ],
]
```

The response is represented by `SidecarResumeResponse`.

## Transport Boundary

`Purple\Contracts\Runtime\SidecarTransport` sends and receives `SidecarEnvelope` instances. It does not prescribe HTTP, Unix sockets, queues, or in-process fakes.

`SidecarResumeClient` validates that response envelopes match the request run ID and response type.

`SidecarDurableRunResumer` demonstrates durable-store integration by loading a `DurableRunRecord`, creating a resume request, and sending it through a `SidecarResumeClient`.

## Composer Baseline

The test suite uses `CallbackSidecarTransport`, so durable resume behavior is covered without a running sidecar service or network dependency.
