---
work_package_id: WP02
title: CLI Native Compatibility Runner
dependencies:
- WP01
requirement_refs:
- FR-004
- FR-005
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
subtasks: []
agent: codex
shell_pid: '3377003'
history: []
agent_profile: implementer-ivan
authoritative_surface: src/Cli/
execution_mode: code_change
model: ''
owned_files:
- src/Cli/PurpleCli.php
- tests/Cli/PurpleCliTest.php
role: implementer
tags: []
---

# Work Package Prompt: WP02 - CLI Native Compatibility Runner

## Goal

Expose the native compatibility prototype through the existing `bin/purple` CLI without requiring a compiled extension for Composer-safe validation.

## Scope

Owned files:

- `src/Cli/PurpleCli.php`
- `tests/Cli/PurpleCliTest.php`

Requirement refs: FR-004, FR-005.

## Tasks

T001: Add a `native` CLI command family to `PurpleCli` help and routing.

T002: Add `native check fixture` to run the compatibility harness with an injected PHP fixture invoker.

T003: Add `native check extension [extension-name]` to run the compatibility harness against the optional extension bridge and report unavailable when the extension/function is missing.

T004: Add CLI tests for fixture success, unavailable extension output, and usage/error handling.

## Verification

- `vendor/bin/phpunit -c phpunit.xml.dist tests/Cli/PurpleCliTest.php`
- `bin/purple native check fixture`
- `bin/purple native check extension definitely_missing_purple_native_extension`
- `composer check`
- `git diff --check`

## Guardrails

Do not create a separate binary, read secrets, use STDIN interactivity, open network sockets, require sidecar state, or make default Composer validation depend on an installed native extension.
