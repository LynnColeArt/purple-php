---
work_package_id: WP01
title: Native Acceptance Boundary
dependencies: []
requirement_refs:
- FR-001
- FR-002
- FR-006
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-runtime-continuation-mega-mission-01KTA3AD
base_commit: a552710625dba5785da7c5123a9ca519853a3dcf
created_at: '2026-06-04T21:28:44.719780+00:00'
subtasks:
- T001
- T002
- T003
- T004
assignee: codex
agent: "codex"
shell_pid: '3377003'
history: []
agent_profile: implementer-ivan
authoritative_surface: src/Contracts/Runtime/
execution_mode: code_change
model: ''
owned_files:
- src/Contracts/Runtime/NativeRuntime.php
- src/Runtime/PhpExtensionBridge.php
- src/Runtime/NativeRuntimeResult.php
- src/Runtime/RuntimeMetrics.php
- src/Runtime/RuntimeException.php
- tests/Runtime/PhpExtensionBridgeTest.php
- tests/Runtime/NativeRuntimeReadinessTest.php
- tests/Runtime/NativeAcceptance/**
- docs/enterprise/native-runtime-acceptance.md
role: implementer
tags: []
---

# WP01: Native Acceptance Boundary

## Objective

Define the first native extension acceptance boundary without requiring a compiled extension during Composer-mode validation.

## Scope

Create a reusable PHP-level acceptance fixture or test surface for any future native implementation that claims compatibility with Purple PHP runtime contracts.

### Subtask T001: Acceptance Contract Shape

Document the required native invocation behavior: operation, payload, status, structured payload output, runtime metrics, and failure semantics.

Files: `docs/enterprise/native-runtime-acceptance.md`, `src/Contracts/Runtime/NativeRuntime.php`

### Subtask T002: Compatibility Fixture

Add or refine tests that can be pointed at an injected native invoker and reused by a compiled extension test later.

Files: `tests/Runtime/NativeAcceptance/**`, `tests/Runtime/PhpExtensionBridgeTest.php`

### Subtask T003: Error and Availability Behavior

Ensure unavailable extensions, invalid operations, invalid payloads, and malformed native responses fail closed with clear exceptions.

Files: `src/Runtime/PhpExtensionBridge.php`, `tests/Runtime/**`

### Subtask T004: Metrics Requirements

Ensure `RuntimeMetrics` expectations are explicit: duration in milliseconds, memory delta in bytes, and no negative duration.

Files: `src/Runtime/RuntimeMetrics.php`, `tests/Runtime/**`

## Acceptance Criteria

1. Composer validation exercises the native boundary without loading a native extension.
2. The acceptance fixture can be reused by a future compiled extension.
3. Failure modes are explicit and tested.
4. Composer mode remains valid when no extension is installed.

## Verification

```bash
composer check
```

## Activity Log

- 2026-06-04T21:34:06Z – codex – shell_pid=3377003 – WP01 implemented in lane-a commit 99a555f. Validation: composer check passed with 106 tests, PHPStan clean, php-cs-fixer dry-run clean.
- 2026-06-04T21:36:03Z – codex – shell_pid=3377003 – Review passed. Scoped to WP01-owned files; composer check passed after final rebase.
