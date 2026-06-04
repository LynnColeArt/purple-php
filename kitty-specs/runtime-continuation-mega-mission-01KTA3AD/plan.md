# Implementation Plan: Runtime Continuation Mega-Mission

**Branch**: `main` | **Date**: 2026-06-04 | **Spec**: `kitty-specs/runtime-continuation-mega-mission-01KTA3AD/spec.md`
**Input**: Feature specification from `kitty-specs/runtime-continuation-mega-mission-01KTA3AD/spec.md`

## Summary

Implement Phase 5.1 as a runtime-continuation mission for Purple PHP. The work should make the optional native/sidecar runtime path more executable by adding a native acceptance boundary, a durable sidecar resume contract, a documented enterprise adapter split decision, and Composer-first guardrails.

This is not a native runtime build mission. It is a contract, test, and documentation mission that prepares future native and sidecar implementation without making native installation part of normal Composer validation.

## Technical Context

**Language/Version**: PHP 8.2+ SDK, validated locally on PHP 8.3.6  
**Primary Dependencies**: Composer autoload, PHPUnit 11.5, PHPStan 2.1, php-cs-fixer 3.x  
**Storage**: File-backed durable run records through `FileDurableRunStore`; no database migration  
**Testing**: PHPUnit contract/unit tests, PHPStan static analysis, php-cs-fixer dry run, targeted examples  
**Target Platform**: Composer-installed PHP applications with optional sidecar/native runtime deployment paths  
**Project Type**: Single Composer-first PHP SDK repository  
**Performance Goals**: Preserve runtime metric reporting through `RuntimeMetrics`; avoid adding network calls to default tests  
**Constraints**: Native extension and sidecar process remain optional; Composer mode must validate without compiled extensions, cloud SDKs, or running services  
**Scale/Scope**: Four reviewable work packages touching `src/Runtime/**`, sidecar runtime contracts, docs/roadmap artifacts, tests, and examples

## Architecture

### Native Acceptance Boundary

The native acceptance surface should remain PHP-level and contract-oriented:

- `NativeRuntime` defines the invocation contract.
- `PhpExtensionBridge` remains the adapter for an injected invoker or compatible extension function.
- A new acceptance fixture should specify required behavior for status, operation, payload, metrics, errors, and unavailable extension handling.

Future compiled implementations should be able to run the same acceptance expectations by exposing a compatible invoker.

### Sidecar Durable Resume Contract

Sidecar resume should build on the existing `SidecarProtocol` and `SidecarEnvelope` shape:

- request envelope type such as `agent.run.resume.request`
- response envelope type such as `agent.run.resume.response`
- required fields: `run_id`, resume action, durable store reference or state pointer, status, and runtime metadata
- injectable transport for tests and examples

The contract should not require an actual HTTP server in this mission. A fake transport is enough to validate payload shape and behavior.

### Enterprise Adapter Split Decision

The first split decision should be recorded as an ADR or enterprise doc section. It should identify:

- candidate package name
- current files likely to move later
- boundary between core SDK and adapter package
- reasons to split now or defer
- Composer-first impact

Likely candidates are provider adapters, domain workflow ports, or runtime sidecar/native package surfaces. The plan should pick one and explain the tradeoff.

### Composer Baseline Guardrail

Composer mode remains the primary product path. Guardrails should ensure:

- tests do not depend on native extension availability
- tests do not require a running sidecar
- examples use fake or injectable transports
- deployment readiness marks native/sidecar as optional
- docs explicitly preserve the optional-native boundary

## Project Structure

### Documentation

```text
kitty-specs/runtime-continuation-mega-mission-01KTA3AD/
├── spec.md
├── plan.md
├── tasks.md
├── wps.yaml
└── tasks/
    ├── WP01-native-acceptance-boundary.md
    ├── WP02-sidecar-durable-resume-contract.md
    ├── WP03-enterprise-adapter-split-decision.md
    └── WP04-composer-baseline-guardrails.md
```

### Source Code

```text
src/
├── Contracts/Runtime/
├── Runtime/
│   ├── Durable/
│   ├── Sandbox/
│   └── Sidecar/
└── Deployment/

tests/
├── Runtime/
├── Deployment/
└── SdkTest.php

docs/
└── enterprise/

examples/
└── runtime/
```

**Structure Decision**: Keep Phase 5.1 inside the existing SDK repository. Do not split packages during this mission; record the split decision first and let a later mission perform physical package extraction if still warranted.

## Work Package Strategy

WP01 should establish the native acceptance boundary first because it defines what future native implementations must prove without changing Composer mode.

WP02 can then add sidecar durable resume contracts using the existing sidecar protocol and durable run store.

WP03 is documentation/architecture decision work and can run after WP01/WP02 clarify the runtime package shape.

WP04 closes the mission by checking docs, deployment readiness, validation, and examples for Composer-first optional-native behavior.

## Risks

Risk: The mission accidentally implies that native extension installation is required.

Mitigation: WP04 owns explicit Composer-mode tests/docs and optional-native wording.

Risk: The sidecar contract becomes too HTTP-specific before a sidecar process exists.

Mitigation: model request/response contracts and injectable transports first; defer real HTTP service behavior.

Risk: Adapter split decision becomes a premature package extraction.

Mitigation: make WP03 an ADR/documentation decision only unless implementation evidence demands otherwise.

Risk: Native acceptance test overfits the PHP bridge rather than a future compiled implementation.

Mitigation: phrase acceptance around `NativeRuntime` behavior and reusable fixtures, not internal bridge implementation details.

## Verification

Expected validation for implementation work:

```bash
composer check
php examples/runtime/durable-sidecar-handoff.php
git diff --check
```

Additional targeted tests should be added for new native acceptance and sidecar durable-resume contracts.
