---
work_package_id: WP03
title: Native Compatibility Docs And Mission Evidence
dependencies:
- WP01
- WP02
requirement_refs:
- FR-006
- FR-007
- FR-008
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
subtasks: []
agent: codex
shell_pid: ''
history: []
agent_profile: implementer-ivan
authoritative_surface: docs/
execution_mode: code_change
model: ''
owned_files:
- README.md
- outline.md
- docs/enterprise/README.md
- docs/enterprise/native-runtime-acceptance.md
- docs/architecture/001-enterprise-adapter-split.md
role: implementer
tags: []
---

# Work Package Prompt: WP03 - Native Compatibility Docs And Mission Evidence

## Goal

Document the native compatibility prototype and record mission evidence while preserving the Composer-first, optional-native baseline.

## Scope

Owned files:

- `README.md`
- `outline.md`
- `docs/enterprise/README.md`
- `docs/enterprise/native-runtime-acceptance.md`
- `docs/architecture/001-enterprise-adapter-split.md`
- `kitty-specs/native-extension-compatibility-prototype-01KTAS1B/acceptance-matrix.json`
- `kitty-specs/native-extension-compatibility-prototype-01KTAS1B/issue-matrix.md`

Requirement refs: FR-006, FR-007, FR-008.

## Tasks

T001: Update native runtime docs with the compatibility command, fixture mode, extension mode, output shape, and optional-native guardrails.

T002: Update roadmap and architecture docs so the native compatibility prototype is marked complete after implementation and remains separate from Bedrock publication, future provider splits, and sidecar daemon/HTTP transport.

T003: Add an acceptance matrix mapping FR-001 through FR-008 to evidence.

T004: Add an issue matrix capturing runtime behavior, CLI behavior, docs, roadmap, and validation evidence.

## Verification

- `composer check`
- `composer check --working-dir=packages/provider-bedrock`
- `bin/purple native check fixture`
- `bin/purple native check extension definitely_missing_purple_native_extension`
- `php -r "json_decode(file_get_contents('kitty-specs/native-extension-compatibility-prototype-01KTAS1B/acceptance-matrix.json'), true, flags: JSON_THROW_ON_ERROR);"`
- `git diff --check`

## Guardrails

Do not claim a compiled extension exists. Do not publish packages. Do not remove Composer-first validation language. Do not make native, sidecar, AWS, or provider-package dependencies mandatory.
