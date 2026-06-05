---
work_package_id: WP02
title: CLI Prototype Command
dependencies:
- WP01
requirement_refs:
- FR-003
- FR-004
tracker_refs: []
planning_base_branch: main
merge_target_branch: main
branch_strategy: Planning artifacts for this mission were generated on main. During /spec-kitty.implement this WP may branch from a dependency-specific base, but completed changes must merge back into main unless the human explicitly redirects the landing branch.
subtasks: []
agent: "codex"
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

# Work Package Prompt: WP02 - CLI Prototype Command

## Goal

Expose the sidecar runtime service prototype through the existing `bin/purple` CLI without requiring a daemon or network service.

## Scope

Owned files:

- `src/Cli/PurpleCli.php`
- `tests/Cli/PurpleCliTest.php`

Requirement refs: FR-003, FR-004.

## Tasks

T001: Add a `sidecar` CLI command family to `PurpleCli` help and routing.

T002: Add a local resume prototype command that accepts a durable run store directory and run ID, constructs the service handler, and writes JSON output.

T003: Keep the command deterministic for tests by avoiding interactive STDIN and live network access.

T004: Add CLI tests for successful local resume output and usage/error handling.

## Verification

- `vendor/bin/phpunit -c phpunit.xml.dist tests/Cli/PurpleCliTest.php`
- `composer check`
- `git diff --check`

## Guardrails

Do not create a separate binary, require a long-running service, read secrets, open network sockets, or make default Composer validation depend on sidecar state.

## Activity Log

- 2026-06-05T01:53:17Z – codex – shell_pid=3377003 – Implemented sidecar resume CLI prototype; focused CLI tests and composer check passed in lane-b.
- 2026-06-05T01:53:19Z – codex – shell_pid=3377003 – Approved after diff review, focused PHPUnit, composer check, and git diff --check in lane-b.
- 2026-06-05T02:05:51Z – codex – shell_pid=3377003 – Marked done after accepted mission squash merge into main at 339d6cd.
