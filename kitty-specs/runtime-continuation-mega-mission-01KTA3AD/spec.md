# Runtime Continuation Mega-Mission

Source plan: `outline.md`

## Mission Goal

Advance Purple PHP from optional runtime readiness surfaces to a concrete Phase 5.1 continuation plan. The mission must make native and sidecar runtime work executable without weakening the Composer-first product boundary.

This mission covers four related decisions and implementation surfaces:

1. First native extension acceptance boundary.
2. Sidecar durable-run resume transport contract.
3. Enterprise adapter package split decision.
4. Composer-mode baseline guardrails.

## Product Context

The previous roadmap mission completed Phases 1 through 5 in the Composer-first SDK. Phase 5 intentionally shipped as optional runtime contracts rather than a compiled native extension:

- `NativeRuntime` and `PhpExtensionBridge`.
- Versioned `SidecarProtocol` envelopes.
- Sandboxed tool execution.
- Durable run storage.
- Runtime metrics.
- Deployment readiness metadata.

This mission should turn that readiness layer into the next executable runtime continuation path while keeping the core SDK installable and useful through Composer alone.

## Target Users

Primary users:

- PHP application teams adopting Purple PHP through Composer.
- Platform teams evaluating sidecar or native runtime deployment.
- Enterprise teams that need durable/resumable agent execution under private infrastructure.

Secondary users:

- Future maintainers splitting provider, domain, or runtime adapters into separate packages.
- Reviewers who need a crisp boundary between Composer SDK behavior and optional native/runtime behavior.

## Functional Requirements

FR-001: Native acceptance boundary

The mission must define the first native extension acceptance test as a contract-level artifact that does not require a compiled extension for Composer-mode validation. The boundary must state what a compatible native bridge must accept, return, reject, and measure.

FR-002: Native bridge compatibility fixture

The mission must add a PHP-level fixture or test that can be reused by a future native implementation to prove compatibility with `NativeRuntime`, `NativeRuntimeResult`, and `RuntimeMetrics`.

FR-003: Sidecar durable resume contract

The mission must define a sidecar transport contract for durable run resume requests and responses. The contract must use the existing versioned sidecar envelope shape and must identify the durable run ID, requested action, resume metadata, and outcome.

FR-004: Durable resume example or test

The mission must demonstrate durable resume behavior without requiring an actual sidecar process. The demonstration may use an injectable transport, fake sidecar, or local protocol round trip.

FR-005: Enterprise adapter split decision

The mission must decide which enterprise adapter package should be split first. The decision must describe ownership boundaries, package name, files likely to move later, and why the split should or should not happen immediately.

FR-006: Composer baseline guardrail

The mission must preserve Composer mode as the stable baseline. Tests and docs must show that native extension and sidecar runtime support remain optional.

FR-007: Roadmap and docs alignment

The mission must update roadmap or enterprise docs so Phase 5.1 is represented as runtime continuation work rather than a reversal of the optional-native strategy.

## Non-Goals

This mission does not need to compile a Zig, C, or PHP native extension.

This mission does not need to run a real sidecar service.

This mission does not need to physically split Composer packages or publish package repositories.

This mission does not need to redesign the existing agent loop, provider abstraction, policy engine, or audit model.

This mission must not introduce native runtime as a required dependency for tests, examples, or Composer-mode SDK use.

## Acceptance Criteria

AC1: The repository contains a native extension acceptance boundary that a future compiled implementation can target.

AC2: Composer validation can exercise the native acceptance boundary through PHP fixtures or contract tests without loading a native extension.

AC3: The repository contains a sidecar durable-resume request/response contract grounded in `SidecarProtocol`.

AC4: Tests prove durable resume transport behavior using a fake or injectable transport.

AC5: Documentation records the first enterprise adapter split decision and explains why it is the right next split.

AC6: Deployment readiness, docs, or tests explicitly preserve optional-native and Composer-first behavior.

AC7: The mission is sliced into reviewable work packages with clear dependencies and verification commands.
