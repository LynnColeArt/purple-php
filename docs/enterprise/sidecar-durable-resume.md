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

## Local Runtime Service Prototype

`SidecarRuntimeService` is the local service-boundary prototype for the same contract. It accepts encoded `purple.sidecar.v1` resume request JSON, decodes it with `SidecarProtocol`, loads the run through `DurableRunStore`, and returns an encoded response envelope.

The prototype returns deterministic outcomes:

- `accepted` when the durable run exists and the action is `continue`.
- `rejected` with `reason: missing_run` when the run is absent.
- `rejected` with `reason: unsupported_action` when the action is not supported.

The CLI can exercise the same boundary without starting a daemon:

```bash
php examples/runtime/durable-sidecar-resume.php
php bin/purple sidecar resume var/runtime/runs run-resume-example local-dev
```

The first command creates a local durable run fixture under `var/runtime/runs`. The second command sends a generated resume request through `SidecarRuntimeService` and writes JSON output with `run_id`, `status`, `message`, and response `metadata`.

This is not a production sidecar daemon. It does not open sockets, supervise processes, add HTTP dependencies, or require a sidecar listener.

## Composer Baseline

The test suite uses `CallbackSidecarTransport` and the local `SidecarRuntimeService`, so durable resume behavior is covered without a required running sidecar process or network dependency.
