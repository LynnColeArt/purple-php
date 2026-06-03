# Purple PHP Work Packages

These work packages are intentionally large. Spec Kitty should treat them as long-horizon implementation slices that preserve product coherence rather than as small tickets.

## WP01: SDK Substrate and Core Contracts

Create the Composer project, baseline PHP tooling, public namespace, test harness, fake provider, and foundational contracts for provider, secret, policy, audit, prompt, schema, and smart-function execution.

Status: pending

Depends on: none

Output: a tested SDK skeleton that can host the smart-function MVP.

## WP02: Smart-Functions MVP Vertical Slice

Implement the first shippable smart-function experience with typed input/output, prompt templates, schema validation, fake provider support, OpenAI provider support, environment secrets, basic policy checks, audit records, retries, error handling, and examples.

Status: pending

Depends on: WP01

Output: MVP 1 complete.

## WP03: Chat, Tool Contracts, and CLI

Add stateful chat sessions, streaming-ready response handling, initial PHP tool contracts, side-effect metadata, role/session context, and a `purple` CLI for local runs, diagnostics, demos, and audit inspection.

Status: pending

Depends on: WP01, WP02

Output: Purple PHP supports the second product capability layer and a practical developer CLI.

## WP04: Looping Agents, Approvals, and Runtime Hooks

Implement goal-driven agent loops, bounded steps, budgets, retry/failure recovery, typed tool execution, approval workflow contracts, replayable tool logs, and runtime hooks around provider requests, tool calls, approval decisions, run steps, policy violations, and failures.

Status: pending

Depends on: WP02, WP03

Output: Purple PHP supports governed looping agents with enterprise-safe extension points.

## WP05: Enterprise Domain Ports and Deployment Readiness

Add enterprise-facing workflow ports, example content/ecommerce implementations, advanced policy/audit shape, tenant and data-control hooks, sidecar protocol design, and native runtime readiness without making native required for Composer adoption.

Status: pending

Depends on: WP03, WP04

Output: Purple PHP has a credible enterprise integration and deployment story.

