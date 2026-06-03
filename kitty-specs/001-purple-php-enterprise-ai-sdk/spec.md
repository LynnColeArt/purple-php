# Purple PHP Enterprise AI SDK

Source plan: `outline.md`

## Mission Goal

Build Purple PHP, an enterprise AI modernization SDK for PHP applications. The SDK must let teams add secure, auditable AI behavior to existing CMS, ecommerce, admin, support, catalog, and workflow systems without rewriting their PHP estate.

Purple PHP is Composer-first. Native runtime, sidecar, and PHP extension support are important future deployment modes, but PHP developers must be able to adopt the core SDK with Composer alone.

## Product Thesis

Purple PHP gives enterprise PHP teams three levels of AI capability:

1. Smart functions: typed, auditable AI calls for narrow repeatable tasks.
2. Chatbots: stateful assistants embedded into existing application surfaces.
3. Looping agents: goal-driven runners that use tools under policy, audit, and approval controls.

The first shippable milestone is smart functions. Chatbots, looping agents, hooks, CLI, sidecar, native runtime, and domain adapters remain part of the long-horizon product.

## Target Users

Target users are enterprises, agencies, and platform teams with significant sunk cost in PHP systems, including custom CMS platforms, Magento/ecommerce systems, Drupal/Symfony/Laravel/Zend applications, internal admin panels, product catalogs, order workflows, vendor portals, support dashboards, and legacy PHP tools.

## Functional Requirements

FR-001: Composer-first SDK

The project must expose a developer-facing PHP SDK installable and usable through Composer. The first public package should make smart functions feel natural to PHP developers.

FR-002: Provider abstraction

The SDK must define provider contracts that can support OpenAI first and later Anthropic, Azure, Bedrock, local providers, and brokered/sidecar provider access.

FR-003: Secure secret handling

Provider credentials must not be passed around as raw strings in normal application code. The SDK must provide secret resolver interfaces and an environment secret implementation for the first milestone.

FR-004: Smart functions

The SDK must support single-purpose AI calls with typed inputs, prompt templates, structured outputs, schema validation, retry/error handling, cost metadata, and audit records.

FR-005: Policy and budget checks

Policy must be part of the execution path. Even the first smart-function milestone must have basic checks for allowed providers, allowed models, and budget/run limits.

FR-006: Audit trail

Provider calls, smart-function runs, policy decisions, failures, cost data, and output validation events must be auditable. File-based or SQLite audit storage is acceptable for the first milestone.

FR-007: Testing support

The SDK must include a fake provider and test utilities so behavior can be proven without hitting external providers.

FR-008: Chatbot support

Later phases must support stateful chat sessions with history, streaming, optional tools, role-aware behavior, policy controls, and audit trails.

FR-009: Tool and looping agent support

Later phases must support typed PHP tools, side-effect levels, approval requirements, agent loops, budgets, step limits, replayable tool logs, and failure recovery.

FR-010: Hooks

Later phases must support typed runtime hooks around provider requests, tool calls, approvals, run steps, policy violations, and failures. Command/file hooks may exist for sidecar or native runtime environments, but core enterprise hooks should not depend on shell execution.

FR-011: CLI

Later phases must include a `purple` CLI for local runs, demos, diagnostics, provider checks, and audit inspection.

FR-012: Domain abstractions

The product should remain CMS-agnostic and ecommerce-aware. Domain ports should represent workflows such as searching content, drafting revisions, looking up orders, updating catalog drafts, and requesting approval rather than tying the core to one CMS.

FR-013: Deployment modes

The long-horizon product should support pure Composer mode, sidecar mode, and native extension mode. Pure Composer mode is the adoption baseline.

## Non-Goals

The first milestone does not need to implement chat sessions, looping agents, hooks, CLI, native runtime, sidecar runtime, CMS adapters, ecommerce adapters, or Vault/cloud secret integrations.

Purple PHP should not become a generic chatbot wrapper. It should not require native extension installation for core adoption. It should not make optional hooks responsible for core security enforcement.

## Acceptance Criteria

AC1: A PHP developer can install/use the SDK and run a typed smart function against a fake provider.

AC2: A PHP developer can configure an OpenAI provider using an environment secret resolver without hard-coding a raw API key in application code.

AC3: Smart function output is validated against an explicit schema or DTO contract, and invalid output fails clearly.

AC4: Every smart-function run creates an audit record with provider, model, prompt/template identity, status, validation status, cost metadata when available, and policy decisions.

AC5: Basic policy checks are enforced before provider execution.

AC6: Tests cover the fake provider, smart-function runner, schema validation, policy denial, audit logging, and secret redaction behavior.

AC7: The public API makes the long-term path to chat, tools, agents, hooks, and CLI visible without forcing them into MVP 1.
