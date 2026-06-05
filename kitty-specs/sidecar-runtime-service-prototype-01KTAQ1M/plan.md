# Implementation Plan: Sidecar Runtime Service Prototype

## Summary

Add a small, optional sidecar runtime service prototype around the existing durable-resume contract. The implementation will stay in the core SDK because it is a contract prototype, not a new package or required runtime. It will provide a reusable service handler, a CLI-accessible local exercise path, docs, and mission evidence.

## Architecture And Boundaries

The prototype belongs to the existing runtime sidecar surface:

- `src/Runtime/Sidecar` owns protocol, request/response, transport, client, resumer, and the new service handler.
- `src/Cli/PurpleCli.php` owns the local prototype command surface.
- `tests/Runtime/Sidecar` and `tests/Cli` own Composer-safe behavior coverage.
- `docs/enterprise/sidecar-durable-resume.md`, `docs/enterprise/README.md`, `README.md`, `outline.md`, and architecture docs own adoption guidance.

The service handler should be a plain PHP object that accepts encoded envelope JSON and returns encoded envelope JSON. The CLI may construct it with `FileDurableRunStore`, but the handler itself should depend on `DurableRunStore` so tests can exercise it without a daemon or filesystem coupling beyond existing fixtures.

## Data Flow

1. A durable run is saved in a `DurableRunStore`.
2. A caller creates or provides an `agent.run.resume.request` envelope.
3. The sidecar runtime service decodes the envelope with `SidecarProtocol`.
4. The service validates the request type and action.
5. The service loads the run by ID from `DurableRunStore`.
6. The service returns an encoded `agent.run.resume.response` envelope:
   - `accepted` when the run exists and the action is supported.
   - `rejected` when the run is missing.
   - `rejected` when the action is unsupported.
7. The CLI command writes a JSON payload summarizing the prototype response.

Malformed envelope decoding should continue to fail loudly through the existing runtime exception path.

## Work Packages

WP01 adds the reusable sidecar runtime service handler and runtime tests.

WP02 adds the CLI-accessible prototype path and CLI tests.

WP03 updates docs, roadmap status, and mission evidence.

## Verification

Required validation:

- `composer check`
- `composer check --working-dir=packages/provider-bedrock`
- `php examples/runtime/durable-sidecar-resume.php`
- `git diff --check`

Mission evidence should also parse the acceptance matrix JSON after it is added.

## Risks

| Risk | Mitigation |
| --- | --- |
| Prototype implies a production daemon exists. | Use "local prototype" language and keep daemon/network work out of scope. |
| CLI path couples default validation to runtime state. | Use local temporary stores in tests and examples; no command is invoked by Composer install. |
| Sidecar response semantics become too broad. | Limit statuses to deterministic accepted/rejected outcomes for resume requests. |
| Runtime track blurs into provider/package work. | Keep docs explicit: this is sidecar runtime contract work, not Bedrock release or provider split work. |

## Rollout

This mission lands as normal SDK changes. No service is deployed and no package is published. Users can try the prototype locally through `bin/purple` after installing Composer dependencies.
