---
work_package_id: WP04
title: Docs And Mission Evidence
dependencies:
- WP01
- WP02
- WP03
requirement_refs:
- FR-008
- FR-009
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
phase: Phase 4 - Docs And Evidence
assignee: ''
agent: "codex"
shell_pid: '3377003'
history:
- timestamp: '2026-06-05T00:10:00Z'
  agent: system
  action: Prompt generated via Bedrock provider package split task authoring
agent_profile: implementer-ivan
authoritative_surface: docs/
execution_mode: code_change
model: ''
owned_files:
- README.md
- docs/architecture/001-enterprise-adapter-split.md
- docs/enterprise/README.md
- outline.md
role: implementer
tags: []
---

# Work Package Prompt: WP04 - Docs And Mission Evidence

## Goal

Document the Bedrock package split and record acceptance evidence showing the provider package is optional and core Composer validation remains stable.

## Scope

Owned files:

- `README.md`
- `outline.md`
- `docs/architecture/001-enterprise-adapter-split.md`
- `docs/enterprise/README.md`

Requirement refs: FR-008, FR-009, FR-006, FR-007.

## Tasks

T001: Update README and enterprise docs to describe `purple-php/provider-bedrock` as optional and show the new construction path.

T002: Update ADR 001 so the Bedrock split is no longer deferred and records the implemented package boundary.

T003: Update `outline.md` to show the Bedrock provider split as the active/completed package-track milestone, depending on implementation state.

T004: Add `acceptance-matrix.json` mapping FR-001 through FR-009 to verification commands and file evidence.

T005: Add `issue-matrix.md` with fixed rows for package skeleton, extraction/factory, core decoupling, validation, and documentation.

## Verification

- `composer check`
- `composer check --working-dir=packages/provider-bedrock`
- `spec-kitty review --mission bedrock-provider-package-split-01KTAHKT --mode post-merge` after merge
- `git diff --check`

## Guardrails

Keep docs honest: do not imply Packagist publication, a separate GitHub repository, AWS SDK support, live AWS signing, or runtime/native package extraction.

## Activity Log

- 2026-06-05T00:37:07Z – codex – shell_pid=3377003 – Implemented docs and mission evidence in lane-d commit 42308ca: updated README, enterprise docs, ADR 001, outline, acceptance matrix, and issue matrix. Evidence: composer check passed, composer check --working-dir=packages/provider-bedrock passed, acceptance-matrix.json parses, git diff --check passed, guardrail search found only intentional migration/history references.
- 2026-06-05T00:37:51Z – codex – shell_pid=3377003 – Approved WP04 at 42308ca. Review evidence: diff limited to README, enterprise docs, ADR 001, outline, acceptance matrix, and issue matrix; docs keep Bedrock as a local optional monorepo package and avoid Packagist/AWS/live/runtime claims; acceptance matrix parses; git diff --check passed; root composer check and provider package composer check passed after lane-local composer install.
