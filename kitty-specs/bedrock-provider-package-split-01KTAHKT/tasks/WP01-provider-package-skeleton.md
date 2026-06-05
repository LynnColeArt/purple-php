---
work_package_id: WP01
title: Provider Package Skeleton
dependencies: []
requirement_refs:
- FR-001
- FR-003
- FR-006
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
base_branch: kitty/mission-bedrock-provider-package-split-01KTAHKT
base_commit: 28b5ef22bfab3004c7e2c9e0ba49daf05a1f2e76
created_at: '2026-06-05T00:16:17.544945+00:00'
subtasks:
- T001
- T002
- T003
- T004
phase: Phase 1 - Package Setup
assignee: ''
agent: "codex"
shell_pid: '3377003'
history:
- timestamp: '2026-06-05T00:10:00Z'
  agent: system
  action: Prompt generated via Bedrock provider package split task authoring
agent_profile: implementer-ivan
authoritative_surface: packages/provider-bedrock/
execution_mode: code_change
model: ''
owned_files:
- packages/provider-bedrock/.php-cs-fixer.dist.php
- packages/provider-bedrock/README.md
- packages/provider-bedrock/composer.json
- packages/provider-bedrock/phpstan.neon.dist
- packages/provider-bedrock/phpunit.xml.dist
role: implementer
tags: []
---

# Work Package Prompt: WP01 - Provider Package Skeleton

## Goal

Create the optional Composer package skeleton for `purple-php/provider-bedrock` under `packages/provider-bedrock` without moving provider behavior yet.

## Scope

Owned files:

- `packages/provider-bedrock/composer.json`
- `packages/provider-bedrock/phpunit.xml.dist`
- `packages/provider-bedrock/phpstan.neon.dist`
- `packages/provider-bedrock/.php-cs-fixer.dist.php`
- `packages/provider-bedrock/README.md`

Requirement refs: FR-001, FR-003, FR-006.

## Tasks

T001: Add `packages/provider-bedrock/composer.json` declaring package name `purple-php/provider-bedrock`, PHP `^8.2`, dependency on `purple-php/sdk`, PSR-4 autoload for `Purple\Provider\Bedrock\`, package-local test autoload, and scripts for `test`, `analyse`, `lint`, and `check`.

T002: Configure the provider package to resolve the local root SDK through a Composer path repository for development validation while keeping root `composer.json` free of a dependency on the provider package.

T003: Add package-local PHPUnit, PHPStan, and php-cs-fixer configuration files that can run from `packages/provider-bedrock`.

T004: Add a short package README describing that this is an optional Bedrock provider package and that default tests use injectable transports instead of AWS calls.

## Verification

- `composer validate --working-dir=packages/provider-bedrock --strict`
- `composer install --working-dir=packages/provider-bedrock`
- `composer check`

## Guardrails

Do not move `BedrockProvider` in this WP. Do not add AWS SDK dependencies. Do not change root SDK provider behavior yet.

## Activity Log

- 2026-06-05T00:19:29Z – codex – shell_pid=3377003 – WP01 implementation committed on lane-a as cc8074a; validation: composer validate --working-dir=packages/provider-bedrock --strict, composer install --working-dir=packages/provider-bedrock --no-interaction, composer check.
- 2026-06-05T00:21:29Z – codex – shell_pid=3377003 – Approved WP01 package skeleton. Evidence: composer validate --working-dir=packages/provider-bedrock --strict; composer install --working-dir=packages/provider-bedrock --no-interaction; composer check; git diff --check main..HEAD.
