---
work_package_id: WP03
title: Core Decoupling And Composer Baseline
dependencies:
- WP01
- WP02
requirement_refs:
- FR-003
- FR-004
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
phase: Phase 3 - Core Decoupling
assignee: ''
agent: "codex"
shell_pid: '3377003'
history:
- timestamp: '2026-06-05T00:10:00Z'
  agent: system
  action: Prompt generated via Bedrock provider package split task authoring
agent_profile: implementer-ivan
authoritative_surface: src/Sdk.php
execution_mode: code_change
model: ''
owned_files:
- composer.json
- src/ProviderProfile.php
- src/Sdk.php
- tests/ProviderProfileTest.php
- tests/SdkTest.php
role: implementer
tags: []
---

# Work Package Prompt: WP03 - Core Decoupling And Composer Baseline

## Goal

Remove root SDK runtime coupling to the optional Bedrock provider package while preserving core provider contracts, profile defaults, and Composer-first validation.

## Scope

Owned files:

- `src/Sdk.php`
- `src/ProviderProfile.php`
- `tests/SdkTest.php`
- `tests/ProviderProfileTest.php`
- `composer.json`

Requirement refs: FR-003, FR-004, FR-006, FR-007.

## Tasks

T001: Remove `Purple\Provider\Bedrock\BedrockProvider` imports and any `Sdk::bedrock()` implementation from root core, or replace it with a failure-free compatibility path that does not require the optional class at autoload time.

T002: Keep `ProviderProfile::bedrock()` in core unless a tested replacement profile story is explicitly added and documented.

T003: Update root SDK tests so they no longer instantiate a Bedrock provider from core. Keep Azure, OpenAI, sidecar, fake provider, and baseline tests intact.

T004: Prove root Composer validation passes without root requiring `purple-php/provider-bedrock`.

## Verification

- `composer install`
- `composer check`
- `vendor/bin/phpunit -c phpunit.xml.dist tests/SdkTest.php tests/ProviderProfileTest.php`
- `rg -n "BedrockProvider|Provider\\Bedrock|Sdk::bedrock" src tests composer.json`

## Guardrails

Do not remove provider contracts or `Sdk::fromProvider()`. Do not add the provider package to root `require` or `require-dev` unless the mission plan is updated to explain why root validation still proves optionality.

## Activity Log

- 2026-06-05T00:30:01Z – codex – shell_pid=3377003 – Implemented core decoupling in lane-c commit 69c9ee2: removed root Sdk::bedrock factory and root Bedrock provider references while preserving package-local BedrockSdk. Evidence: root composer check passed, package composer check passed, rg confirms Bedrock references are package-local.
