---
work_package_id: WP01
title: Native Compatibility Harness
dependencies: []
requirement_refs:
- FR-001
- FR-002
- FR-003
- FR-005
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: main
base_commit: ac0514e5b348b60882be6024c77d623139c0077b
created_at: '2026-06-05T02:20:19.452191+00:00'
subtasks: []
agent: "codex"
shell_pid: "3377003"
history: []
agent_profile: implementer-ivan
authoritative_surface: src/Runtime/
execution_mode: code_change
model: ''
owned_files:
- src/Runtime/NativeRuntimeCompatibility.php
- src/Runtime/NativeRuntimeCompatibilityReport.php
- src/Runtime/NativeRuntimeReadiness.php
- tests/Runtime/NativeRuntimeCompatibilityTest.php
- tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php
role: implementer
tags: []
---

# Work Package Prompt: WP01 - Native Compatibility Harness

## Goal

Add a reusable native runtime compatibility harness that runs the existing acceptance ping contract against any `NativeRuntime` implementation and normalizes the verdict.

## Scope

Owned files:

- `src/Runtime/NativeRuntimeCompatibility.php`
- `src/Runtime/NativeRuntimeCompatibilityReport.php`
- `src/Runtime/NativeRuntimeReadiness.php`
- `tests/Runtime/NativeRuntimeCompatibilityTest.php`
- `tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php`

Requirement refs: FR-001, FR-002, FR-003, FR-005.

## Tasks

T001: Add a compatibility report object that exposes `compatible`, `status`, `operation`, `payload`, `metrics`, `message`, and `toArray()`.

T002: Add a compatibility harness that invokes `runtime.acceptance.ping` with a deterministic tenant payload and validates operation, status, answer payload, and non-negative metrics.

T003: Normalize bridge/runtime failures into `incompatible` or `unavailable` reports while preserving direct `PhpExtensionBridge` behavior for SDK callers.

T004: Add runtime tests covering a compatible fixture, malformed response, unavailable extension, readiness metadata, and continued native acceptance fixture behavior.

## Verification

- `vendor/bin/phpunit -c phpunit.xml.dist tests/Runtime/NativeRuntimeCompatibilityTest.php tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php`
- `composer check`
- `git diff --check`

## Guardrails

Do not implement a compiled extension, Zig runtime, C ABI, FFI binding, native package, sidecar daemon, network call, cloud SDK dependency, or provider package release behavior.

## Activity Log

- 2026-06-05T02:23:37Z – codex – shell_pid=3377003 – Implemented native compatibility harness and report; focused runtime PHPUnit, composer check, and git diff --check passed in lane-a.
- 2026-06-05T02:25:06Z – codex – shell_pid=3377003 – Started review via action command
