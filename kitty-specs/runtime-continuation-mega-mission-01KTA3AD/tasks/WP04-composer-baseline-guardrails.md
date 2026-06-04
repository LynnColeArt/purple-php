---
work_package_id: WP04
title: Composer Baseline Guardrails
dependencies:
- WP01
- WP02
- WP03
requirement_refs:
- FR-001
- FR-003
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
authoritative_surface: tests/Deployment/
execution_mode: code_change
model: ''
owned_files:
- .gitignore
- composer.json
- README.md
- outline.md
- docs/enterprise/README.md
- src/Deployment/DeploymentReadiness.php
- src/Sdk.php
- tests/Deployment/DeploymentReadinessTest.php
- tests/SdkTest.php
- examples/runtime/durable-sidecar-handoff.php
role: implementer
tags: []
---

# WP04: Composer Baseline Guardrails

## Objective

Close the mission by proving Phase 5.1 keeps Composer mode stable while adding optional runtime continuation contracts.

## Scope

This WP verifies and tightens tests, examples, and docs after WP01 through WP03. It should catch any accidental dependency on native extensions, sidecar services, cloud SDKs, or network services.

### Subtask T001: Deployment Readiness Assertions

Ensure deployment readiness continues to mark sidecar and native extension capabilities as optional.

Files: `src/Deployment/DeploymentReadiness.php`, `tests/Deployment/DeploymentReadinessTest.php`

### Subtask T002: SDK Baseline Assertions

Ensure normal SDK construction and fake-provider examples do not require runtime continuation components.

Files: `src/Sdk.php`, `tests/SdkTest.php`

### Subtask T003: Runtime Example Hygiene

Ensure runtime examples use fake/injectable transports, write generated data only under ignored runtime paths, and remain safe to run locally.

Files: `examples/runtime/durable-sidecar-handoff.php`, `.gitignore`

### Subtask T004: Documentation Guardrail

Update docs to state that Phase 5.1 makes runtime continuation executable but does not make native runtime mandatory.

Files: `README.md`, `outline.md`, `docs/enterprise/**`

### Subtask T005: Full Validation

Run full local validation and record any residual caveats in the implementation summary.

Files: test and documentation surfaces touched by this mission

## Acceptance Criteria

1. `composer check` passes.
2. Runtime examples run without native extensions or sidecar services.
3. Docs preserve Composer-first and optional-native language.
4. No new required production dependency is introduced.

## Verification

```bash
composer check
php examples/runtime/durable-sidecar-handoff.php
git diff --check
```
