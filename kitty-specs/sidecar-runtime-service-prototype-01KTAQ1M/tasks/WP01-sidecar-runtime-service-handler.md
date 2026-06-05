---
work_package_id: WP01
title: Sidecar Runtime Service Handler
dependencies: []
requirement_refs:
- FR-001
- FR-002
- FR-004
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-sidecar-runtime-service-prototype-01KTAQ1M
base_commit: b57d68577a2088c577ef73a64fae8da298caeb8e
created_at: '2026-06-05T01:46:50.913302+00:00'
subtasks: []
agent: codex
shell_pid: '3377003'
history: []
agent_profile: implementer-ivan
authoritative_surface: src/Runtime/Sidecar/
execution_mode: code_change
model: ''
owned_files:
- src/Runtime/Sidecar/SidecarRuntimeService.php
- tests/Runtime/Sidecar/SidecarRuntimeServiceTest.php
role: implementer
tags: []
---

# Work Package Prompt: WP01 - Sidecar Runtime Service Handler

## Goal

Add a reusable local sidecar runtime service handler that speaks the existing durable-resume envelope contract.

## Scope

Owned files:

- `src/Runtime/Sidecar/SidecarRuntimeService.php`
- `tests/Runtime/Sidecar/SidecarRuntimeServiceTest.php`

Requirement refs: FR-001, FR-002, FR-004.

## Tasks

T001: Add `SidecarRuntimeService` under `Purple\Runtime\Sidecar` with a method that accepts raw encoded envelope JSON and returns encoded response envelope JSON.

T002: Use `SidecarProtocol`, `SidecarResumeRequest`, `SidecarResumeResponse`, and `DurableRunStore` instead of duplicating protocol parsing or durable storage logic.

T003: Return deterministic response metadata for accepted runs, missing runs, and unsupported actions.

T004: Add tests for accepted resume, missing run rejection, unsupported action rejection, malformed envelope failure, and response envelope encoding.

## Verification

- `vendor/bin/phpunit -c phpunit.xml.dist tests/Runtime/Sidecar/SidecarRuntimeServiceTest.php`
- `composer check`
- `git diff --check`

## Guardrails

Do not add an HTTP server, process manager, new dependency, daemon requirement, native extension, or live network call. Do not modify provider package release behavior.

## Activity Log

- 2026-06-05T01:49:22Z – codex – shell_pid=3377003 – Moved to for_review
- 2026-06-05T01:49:55Z – codex – shell_pid=3377003 – Approved after diff review, focused PHPUnit, composer check, and git diff --check in lane-a.
