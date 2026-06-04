---
work_package_id: WP02
title: Sidecar Durable Resume Contract
dependencies:
- WP01
requirement_refs:
- FR-003
- FR-004
- FR-006
- FR-007
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
subtasks:
- T001
- T002
- T003
- T004
- T005
assignee: codex
agent: codex
history: []
agent_profile: implementer-ivan
authoritative_surface: src/Runtime/Sidecar/
execution_mode: code_change
model: ''
owned_files:
- src/Contracts/Runtime/SidecarTransport.php
- src/Contracts/Runtime/DurableRunStore.php
- src/Runtime/Sidecar/**
- src/Runtime/Durable/**
- tests/Runtime/Sidecar/**
- tests/Runtime/Durable/**
- examples/runtime/durable-sidecar-resume.php
- docs/enterprise/sidecar-durable-resume.md
role: implementer
tags: []
---

# WP02: Sidecar Durable Resume Contract

## Objective

Add a sidecar transport contract for durable run resume requests and responses using the existing versioned sidecar envelope model.

## Scope

Model durable resume as a protocol contract and fake/injectable transport. Do not require an actual sidecar service or HTTP server.

### Subtask T001: Resume Request and Response Shapes

Define request and response payload shapes for durable run resume operations, including run ID, resume action, state pointer, status, and runtime metadata.

Files: `src/Runtime/Sidecar/**`, `tests/Runtime/Sidecar/**`

### Subtask T002: Transport Contract

Add an injectable sidecar transport contract or runtime client surface that can send and receive `SidecarEnvelope` instances.

Files: `src/Contracts/Runtime/SidecarTransport.php`, `src/Runtime/Sidecar/**`

### Subtask T003: Durable Store Integration

Show how the transport contract interacts with `DurableRunStore` and `DurableRunRecord` without coupling to one persistence backend.

Files: `src/Runtime/Durable/**`, `tests/Runtime/Durable/**`

### Subtask T004: Fake Transport Tests

Add tests for successful resume, missing durable run, unsupported action, and malformed envelope behavior.

Files: `tests/Runtime/Sidecar/**`

### Subtask T005: Runtime Example Update

Extend the runtime handoff example or add a sibling example that demonstrates a durable run resume round trip through the fake transport.

Files: `examples/runtime/durable-sidecar-resume.php`

## Acceptance Criteria

1. Durable resume request and response contracts are represented in code.
2. Tests prove resume behavior without a running sidecar.
3. Existing `SidecarProtocol::VERSION` remains the protocol anchor.
4. Composer mode continues to validate without network services.

## Verification

```bash
composer check
php examples/runtime/durable-sidecar-handoff.php
```
