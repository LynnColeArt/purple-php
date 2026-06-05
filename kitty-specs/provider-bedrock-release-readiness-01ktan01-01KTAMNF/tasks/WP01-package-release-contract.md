---
work_package_id: WP01
title: Package Release Contract
dependencies: []
requirement_refs:
- FR-001
- FR-002
- FR-005
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-provider-bedrock-release-readiness-01ktan01-01KTAMNF
base_commit: 1ff7b42b1b1ee9f22f96da81198ec06fb7faa1c0
created_at: '2026-06-05T01:05:56.006387+00:00'
subtasks:
- T001
- T002
- T003
- T004
phase: Phase 1 - Release Contract
assignee: ''
agent: "codex"
shell_pid: "3377003"
history:
- timestamp: '2026-06-05T01:00:00Z'
  agent: system
  action: Prompt generated for provider Bedrock release-readiness task authoring
agent_profile: implementer-ivan
authoritative_surface: packages/provider-bedrock/
execution_mode: code_change
model: ''
owned_files:
- packages/provider-bedrock/README.md
- packages/provider-bedrock/CHANGELOG.md
- docs/release/provider-bedrock.md
role: implementer
tags: []
---

# Work Package Prompt: WP01 - Package Release Contract

## Goal

Document the release contract for `purple-php/provider-bedrock` without publishing the package.

## Scope

Owned files:

- `packages/provider-bedrock/README.md`
- `packages/provider-bedrock/CHANGELOG.md`
- `docs/release/provider-bedrock.md`

Requirement refs: FR-001, FR-002, FR-005.

## Tasks

T001: Expand the Bedrock package README with current unpublished status, future install command, local monorepo validation commands, and the Composer-first no-AWS/no-sidecar/no-native baseline.

T002: Add `packages/provider-bedrock/CHANGELOG.md` with `0.1.0 - Unreleased` release notes, first-release scope, and migration guidance from root `Sdk::bedrock()` to `Purple\Provider\Bedrock\BedrockSdk::create()`.

T003: Add `docs/release/provider-bedrock.md` with package name, first intended version line, release sequence, Packagist publication checklist, validation commands, and rollback notes.

T004: Ensure release docs do not imply publication has already happened.

## Verification

- `rg -n "purple-php/provider-bedrock|0.1.0|Packagist|composer check --working-dir=packages/provider-bedrock" packages/provider-bedrock docs/release`
- `git diff --check`

## Guardrails

Do not publish the package. Do not add AWS SDK dependencies. Do not add live AWS tests. Do not change provider runtime behavior in this WP.

## Activity Log

- 2026-06-05T01:08:07Z – codex – shell_pid=3377003 – Started review via action command
