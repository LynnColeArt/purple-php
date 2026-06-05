# Sidecar Runtime Service Prototype

Source roadmap: `outline.md` candidate follow-up mission 2 and `docs/enterprise/sidecar-durable-resume.md`.

## Mission Goal

Prototype an optional local sidecar runtime service that speaks Purple PHP's existing `purple.sidecar.v1` durable-resume contract.

The mission must turn the current in-process sidecar resume fixtures into a concrete service boundary: a reusable runtime service handler, a CLI-accessible local prototype path, docs that explain the request/response contract, and validation that proves normal Composer mode still works without a running sidecar process, native extension, cloud SDK, AWS credentials, or live network service.

## Product Context

Phase 5.1 made durable sidecar resume executable through PHP-level contracts, fake transports, and examples. That proved the envelope shape and client-side resumer, but it did not create a sidecar runtime surface that a deployment team could prototype against.

The next useful step is a small local service prototype that can:

- Decode `agent.run.resume.request` envelopes using `SidecarProtocol::VERSION`.
- Look up durable run records through the existing durable run store contract.
- Return `agent.run.resume.response` envelopes with deterministic accepted/rejected outcomes.
- Be exercised through the existing `bin/purple` CLI without requiring a daemon, socket listener, or external service.
- Keep the stable SDK adoption path Composer-first.

## Target Users

Primary users:

- Platform teams evaluating sidecar runtime orchestration for on-prem or managed infrastructure deployments.
- SDK maintainers proving the sidecar contract can cross a service boundary.
- Reviewers checking that sidecar work remains optional and does not burden default validation.

Secondary users:

- Future implementers of an HTTP or long-running sidecar daemon.
- Teams planning native runtime compatibility work after the sidecar boundary is clearer.

## Functional Requirements

| ID | Requirement | Status |
| --- | --- | --- |
| FR-001 | Add a reusable sidecar runtime service handler that accepts raw `purple.sidecar.v1` durable-resume request envelopes and returns encoded response envelopes. | Planned |
| FR-002 | The service handler must load durable run records through `DurableRunStore` and return deterministic accepted/rejected responses for found runs, missing runs, and unsupported resume actions. | Planned |
| FR-003 | Add a CLI-accessible prototype path under `bin/purple` that can exercise the service boundary against a local durable run store without starting a daemon. | Planned |
| FR-004 | Add tests covering service handler success, missing run rejection, unsupported action rejection, malformed envelope handling, and CLI prototype output. | Planned |
| FR-005 | Update sidecar runtime docs and examples so maintainers can see the envelope contract, CLI prototype command, and local-store expectations. | Planned |
| FR-006 | Update roadmap and architecture docs so the sidecar service prototype is represented as a completed runtime-track step, separate from package publication, native extension work, and provider splits. | Planned |
| FR-007 | Capture mission evidence proving Composer-mode root validation still passes without a required sidecar process, native extension, cloud SDK, AWS credentials, or live network dependency. | Planned |

## Non-Goals

This mission must not start, supervise, or require a long-running daemon.

This mission must not add an HTTP server dependency, queue dependency, process manager, native extension, or sidecar package split.

This mission must not make `composer check` require a running sidecar service.

This mission must not publish Packagist packages or alter the Bedrock provider package release track.

This mission must not implement production authentication, multitenant routing, distributed locking, remote state storage, or live sidecar networking.

This mission must not remove the existing injectable fake-transport tests.

## Acceptance Criteria

AC1: A sidecar runtime service class can handle an encoded `agent.run.resume.request` envelope and return an encoded `agent.run.resume.response` envelope using `purple.sidecar.v1`.

AC2: The service returns accepted metadata for an existing durable run and rejected metadata for missing runs and unsupported actions.

AC3: `bin/purple` exposes a sidecar prototype command that writes JSON output and can be tested without STDIN interactivity, a daemon, a sidecar listener, or network access.

AC4: Tests cover the runtime service and CLI prototype behavior.

AC5: Docs explain how the local prototype relates to the durable-resume contract and clearly state that no sidecar process is required for default Composer validation.

AC6: Roadmap docs mark the sidecar runtime service prototype as complete after implementation while leaving native extension compatibility and provider split work as future candidates.

AC7: The mission records issue and acceptance evidence for service behavior, CLI behavior, docs, roadmap updates, and Composer validation.
