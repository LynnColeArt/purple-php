---
work_package_id: WP02
title: Bedrock Provider Extraction And Factory
dependencies:
- WP01
requirement_refs:
- FR-002
- FR-005
- FR-007
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-bedrock-provider-package-split-01KTAHKT-lane-a
base_commit: 65403b95dd6d54065f36f97e4c8eead14690f92e
created_at: '2026-06-05T00:22:26.962552+00:00'
subtasks:
- T001
- T002
- T003
- T004
- T005
phase: Phase 2 - Provider Extraction
assignee: ''
agent: "codex"
shell_pid: '3377003'
history:
- timestamp: '2026-06-05T00:10:00Z'
  agent: system
  action: Prompt generated via Bedrock provider package split task authoring
agent_profile: implementer-ivan
authoritative_surface: packages/provider-bedrock/src/
execution_mode: code_change
model: ''
owned_files:
- packages/provider-bedrock/src/**
- packages/provider-bedrock/tests/**
- src/Provider/Bedrock/BedrockProvider.php
- tests/Provider/Bedrock/BedrockProviderTest.php
role: implementer
tags: []
---

# Work Package Prompt: WP02 - Bedrock Provider Extraction And Factory

## Goal

Move the Bedrock implementation and provider-specific tests into `packages/provider-bedrock`, then add a package-local SDK factory/helper that preserves ergonomic construction through core `Sdk::fromProvider()`.

## Scope

Owned files:

- `src/Provider/Bedrock/BedrockProvider.php`
- `tests/Provider/Bedrock/BedrockProviderTest.php`
- `packages/provider-bedrock/src/**`
- `packages/provider-bedrock/tests/**`

Requirement refs: FR-002, FR-005, FR-007.

## Tasks

T001: Move `BedrockProvider` into `packages/provider-bedrock/src/BedrockProvider.php` while preserving namespace `Purple\Provider\Bedrock`.

T002: Move `BedrockProviderTest` into package-local tests and keep coverage for request construction, endpoint/region handling, response parsing, and usage normalization.

T003: Add a package-local factory/helper, such as `Purple\Provider\Bedrock\BedrockSdk`, that accepts a core `ProviderProfile`, optional audit/policy/schema collaborators, injectable transport, and region, then returns a `Purple\Sdk` via `Sdk::fromProvider()`.

T004: Add tests proving the factory/helper replaces the old core convenience path without AWS credentials or live network calls.

T005: Ensure package tests do not rely on root test namespaces unless explicitly configured in package dev autoload.

## Verification

- `composer install --working-dir=packages/provider-bedrock`
- `composer check --working-dir=packages/provider-bedrock`
- `packages/provider-bedrock/vendor/bin/phpunit -c packages/provider-bedrock/phpunit.xml.dist`

## Guardrails

Do not modify root `Sdk.php` in this WP except for file movement conflicts that cannot be avoided; root decoupling belongs to WP03. Do not add live AWS calls or AWS SDK dependencies.

## Activity Log

- 2026-06-05T00:25:45Z – codex – shell_pid=3377003 – WP02 implementation committed on lane-b as 04c0901; validation: composer install --working-dir=packages/provider-bedrock --no-interaction; composer check --working-dir=packages/provider-bedrock; composer validate --working-dir=packages/provider-bedrock --strict.
