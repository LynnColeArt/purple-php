---
work_package_id: WP02
title: CI Matrix For Root And Provider
dependencies:
- WP01
requirement_refs:
- FR-003
- FR-004
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-provider-bedrock-release-readiness-01ktan01-01KTAMNF
base_commit: 8fd2c0478bd2cb484f0aec08ebbcf1c60d381d37
created_at: '2026-06-05T01:11:25.916926+00:00'
subtasks:
- T001
- T002
- T003
- T004
phase: Phase 2 - CI Validation
assignee: ''
agent: "codex"
shell_pid: "3377003"
history:
- timestamp: '2026-06-05T01:00:00Z'
  agent: system
  action: Prompt generated for provider Bedrock release-readiness task authoring
agent_profile: implementer-ivan
authoritative_surface: .github/workflows/
execution_mode: code_change
model: ''
owned_files:
- .github/workflows/ci.yml
role: implementer
tags: []
---

# Work Package Prompt: WP02 - CI Matrix For Root And Provider

## Goal

Add repository CI that validates the root SDK and optional Bedrock provider package separately.

## Scope

Owned files:

- `.github/workflows/ci.yml`

Requirement refs: FR-003, FR-004.

## Tasks

T001: Add a GitHub Actions CI workflow for pull requests and pushes to `main`.

T002: Add a root SDK job that runs `composer validate --strict`, installs dependencies, and runs `composer check`.

T003: Add a Bedrock provider package job that runs `composer validate --working-dir=packages/provider-bedrock --strict`, installs package dependencies, and runs `composer check --working-dir=packages/provider-bedrock`.

T004: Keep root and provider validation separate so the root SDK job does not require the provider package, AWS credentials, AWS SDK packages, sidecars, or native extensions.

## Verification

- `ruby -e "require 'yaml'; YAML.load_file('.github/workflows/ci.yml')"`
- `rg -n "composer check|packages/provider-bedrock|AWS" .github/workflows/ci.yml`
- `git diff --check`

## Guardrails

Do not add publish tokens, Packagist credentials, deployment jobs, live AWS calls, or required secret configuration.

## Activity Log

- 2026-06-05T01:13:14Z – codex – shell_pid=3377003 – Started review via action command
