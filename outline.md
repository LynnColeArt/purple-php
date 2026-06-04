
# Purple PHP Project Plan

## 1. Product Thesis

Purple PHP is an enterprise AI modernization SDK for existing PHP applications.

The goal is to let organizations add secure, auditable AI behavior to legacy and modern PHP estates without rewriting their CMS, ecommerce platform, admin tools, catalog systems, support workflows, or business logic.

Purple PHP is not a generic chatbot wrapper. It is a Composer-first agent SDK for PHP applications that supports three levels of AI capability:

1. Smart functions
2. Chatbots
3. Looping agents with tools

The native runtime layer may be implemented in Zig and C, but the developer-facing center of gravity is PHP and Composer.

## 2. Target Customer

Purple PHP is aimed at enterprises, agencies, and platform teams with significant sunk cost in PHP-based systems.

Common environments include:

* Custom CMS platforms
* Magento and ecommerce systems
* Drupal, Symfony, Laravel, Zend, and legacy PHP applications
* Internal admin panels
* Product catalogs
* Support dashboards
* Editorial systems
* Order management workflows
* Vendor and customer portals

The core promise:

Purple PHP lets enterprises add secure, auditable AI agents to existing PHP applications without rewriting their CMS, ecommerce platform, or business workflows.

## 3. Core Capability Layers

### 3.1 Smart Functions

Smart functions are single-purpose AI calls with typed inputs and structured outputs.

They are intended for small, controlled, repeatable AI tasks.

Example use cases:

* Classify content
* Summarize support tickets
* Rewrite product descriptions
* Generate metadata
* Extract entities
* Score risk
* Suggest tags
* Generate image alt text
* Normalize messy catalog data

Example shape:

```php
$summary = Purple::function('summarize_article')
    ->input($article)
    ->returns(Summary::class)
    ->run();
```

Smart functions should be the first implementation target because they force the foundational pieces to exist:

* Provider abstraction
* Prompt templates
* Structured output
* Schema validation
* Retry behavior
* Error handling
* Tracing
* Cost tracking
* Audit logging

### 3.2 Chatbots

Chatbots are stateful conversational surfaces embedded into existing PHP applications.

They may be used in:

* Admin panels
* Editorial assistants
* Support dashboards
* Storefront help systems
* Internal employee tools
* Vendor portals

Chatbots should support:

* Conversation history
* Streaming responses
* Optional tools
* Role-aware behavior
* Session persistence
* Policy controls
* Audit trails

Example shape:

```php
$chat = Purple::chat()
    ->withSystem('You help editors manage content.')
    ->withHistory($thread)
    ->withTools([$searchContent, $readPage]);

$reply = $chat->send($message);
```

### 3.3 Looping Agents

Looping agents are goal-driven runners that repeatedly call tools until they complete, fail, hit a policy boundary, request approval, or exhaust a budget.

Example use cases:

* Audit stale content
* Draft product catalog updates
* Triage comments
* Migrate legacy content
* Build editorial reports
* Identify broken pages
* Suggest SEO fixes
* Detect policy violations
* Prepare approval queues
* Clean up messy ecommerce data

Example shape:

```php
$run = Purple::agent()
    ->withGoal('Find outdated pricing pages and draft updates.')
    ->withTools([$searchContent, $draftRevision, $requestApproval])
    ->withPolicy($policy)
    ->run();
```

Looping agents require the strongest governance layer because they can perform repeated actions over business-critical systems.

## 4. Architecture Overview

Purple PHP should be split into a Composer-first PHP SDK with optional runtime acceleration.

### 4.1 PHP SDK

The PHP SDK provides the public developer API.

Responsibilities:

* Agent definitions
* Smart function definitions
* Chat sessions
* Tool contracts
* Provider interfaces
* Policy interfaces
* Secret handling interfaces
* Audit log interfaces
* Event stream interfaces
* Hook configuration
* Schema validation
* Testing helpers

### 4.2 Optional Native Runtime

The native runtime may be written in Zig with C ABI compatibility.

Responsibilities may include:

* High-performance execution support
* Streaming orchestration
* Tool sandboxing support
* Durable run state
* Sidecar communication
* Native extension bridge
* Process isolation
* Low-level policy enforcement

The native layer must remain optional. Many PHP deployments cannot install native extensions.

### 4.3 Deployment Modes

Purple PHP should support three deployment modes:

1. Pure Composer mode
   Runs entirely as PHP packages. Best for broad adoption and constrained hosting environments.

2. Sidecar mode
   PHP talks to a local or private Purple runtime service. Best for Docker, VPS, enterprise, and agency deployments.

3. Native extension mode
   PHP uses an optional `purple_php` extension backed by the native runtime. Best for serious managed infrastructure where performance and deeper integration matter.

## 5. Package Layout

Initial package family:

```text
purple-php/sdk
purple-php/core
purple-php/providers
purple-php/provider-openai
purple-php/provider-anthropic
purple-php/provider-azure
purple-php/provider-bedrock
purple-php/secrets-env
purple-php/secrets-encrypted
purple-php/secrets-vault
purple-php/policy
purple-php/audit
purple-php/tools
purple-php/cms
purple-php/ecommerce
purple-php/testing
purple-php/native
```

Possible namespace:

```php
Purple\
Purple\Agent\
Purple\Chat\
Purple\Function\
Purple\Provider\
Purple\Tool\
Purple\Policy\
Purple\Audit\
Purple\Security\
Purple\CMS\
Purple\Ecommerce\
Purple\Hooks\
```

CLI name:

```bash
purple
```

Native extension name:

```text
purple_php
```

## 6. Provider Security

Provider connection must be secure by default.

Purple PHP should avoid encouraging raw API keys in application code.

Provider configuration should support:

* Environment variables
* Encrypted application configuration
* Vault integration
* Cloud secret stores
* Sidecar or brokered provider access
* Per-tenant credentials
* Model allowlists
* Budget limits
* Key rotation
* Provider-level audit logs

Example shape:

```php
$provider = Purple::provider('openai')
    ->withSecret(Secret::env('OPENAI_API_KEY'))
    ->allowModels(['gpt-5.5'])
    ->withBudget(monthlyUsd: 500);
```

Security requirements:

* Never log raw secrets
* Redact secrets from traces
* Support field-level sensitivity
* Fail closed when policy, budget, or credentials are invalid
* Make provider calls separately auditable from tool calls
* Support data retention controls
* Support enterprise data residency requirements

## 7. Tool System

Tools are typed PHP capabilities that agents can call.

A tool should define:

* Name
* Description
* Input schema
* Output schema
* Permissions
* Side effect level
* Approval requirements
* Retry behavior
* Audit behavior

Example tool categories:

* Read-only tools
* Drafting tools
* Search tools
* Classification tools
* Revision tools
* Publishing tools
* Order lookup tools
* Customer lookup tools
* Catalog update tools
* Approval queue tools
* Reporting tools

Side effect levels should be explicit:

```text
read_only
draft_only
writes_pending_approval
writes_directly
destructive
external_side_effect
```

Agents should not call high-risk tools without policy permission.

## 8. Policy Engine

The policy engine decides what an agent is allowed to do.

Policy should cover:

* Which providers are allowed
* Which models are allowed
* Which tools are allowed
* Which users can run which agents
* Which actions require approval
* Which data fields may be sent to providers
* Which operations are read-only
* Which operations can write drafts
* Which operations can publish
* Which operations are forbidden
* Budget limits
* Run limits
* Tool call limits
* Time limits

Policy must be part of the core execution path. It should not be implemented only as optional userland hooks.

## 9. Audit Trail

Purple PHP should have first-class audit logs.

Audit events should include:

* Run started
* Run completed
* Run failed
* Provider request started
* Provider response received
* Tool call requested
* Tool call approved
* Tool call denied
* Tool call completed
* File or content diff generated
* Approval requested
* Approval accepted
* Approval rejected
* Policy violation
* Budget exceeded
* Hook executed
* Hook failed

Audit logs should support replay and debugging where possible.

Enterprise users need to answer:

* What did the agent do?
* Why did it do that?
* What data did it see?
* Which model was used?
* Which tools were called?
* Which user initiated the run?
* What changed?
* Who approved it?
* How much did it cost?

## 10. Codex/Claude Code Style Hooks

Purple PHP should support Codex/Claude Code style hooks as agent runtime interception points.

These are not CMS hooks. They are programmable guardrails and automation points around the agent’s own execution.

They allow teams to run scripts, commands, PHP callables, webhooks, or sidecar policies before and after important agent actions.

### 10.1 Hook Goals

Hooks should allow developers and enterprises to:

* Validate agent actions before execution
* Block unsafe commands
* Enforce formatting and linting
* Run tests after file changes
* Redact data before provider calls
* Log or stream events to external systems
* Require approval before dangerous actions
* Trigger notifications
* Add custom policy checks
* Attach observability
* Transform tool inputs or outputs
* Stop runaway loops

### 10.2 Hook Events

Initial hook events:

```text
BeforeRun
AfterRun
BeforeAgentStep
AfterAgentStep
BeforeProviderRequest
AfterProviderResponse
BeforeToolCall
AfterToolCall
BeforeCommand
AfterCommand
BeforeFileRead
AfterFileRead
BeforeFileWrite
AfterFileWrite
BeforeDiffApply
AfterDiffApply
BeforeApprovalRequest
AfterApprovalDecision
OnPolicyViolation
OnBudgetExceeded
OnRunFailed
```

### 10.3 Hook Configuration

Hooks should be configurable in a project file.

Possible file:

```text
purple.yaml
```

Example:

```yaml
hooks:
  before_provider_request:
    - php: App\Purple\Hooks\RedactPiiHook

  before_tool_call:
    - php: App\Purple\Hooks\PolicyCheckHook

  after_file_write:
    - command: "vendor/bin/php-cs-fixer fix --dry-run"
      on_failure: block

  after_diff_apply:
    - command: "vendor/bin/phpunit"
      on_failure: require_approval

  before_command:
    - php: App\Purple\Hooks\CommandAllowlistHook
```

### 10.4 Hook Results

Every hook should return a structured result.

Possible outcomes:

```text
allow
block
warn
modify
require_approval
retry
fail_run
```

Example PHP interface:

```php
interface Hook
{
    public function handle(HookContext $context): HookResult;
}
```

Example result:

```php
return HookResult::block('Command attempts to delete production data.');
```

### 10.5 Hook Context

Hook context should include:

* Run ID
* Agent ID
* User ID
* Event type
* Current step
* Tool name
* Tool input
* Proposed command
* Proposed file path
* Diff summary
* Provider name
* Model name
* Budget state
* Policy state
* Environment
* Metadata

Sensitive values must be redacted by default.

### 10.6 Hook Safety

Hooks themselves are powerful and must be governed.

Rules:

* Hooks must be auditable
* Hooks must have timeouts
* Hooks must have failure behavior
* Hooks must not silently bypass core policy
* Hooks must run with least privilege
* Hooks must declare whether they can mutate context
* Hooks must be distinguishable from core enforcement
* Hooks must be disabled or restricted in untrusted environments

Core security checks should not depend on optional hooks.

Hooks are extension points. Policy is enforcement.

## 11. Domain Abstractions

Purple PHP should avoid tying itself to one CMS.

Instead, it should define enterprise-friendly interfaces.

Possible abstractions:

```php
interface ContentRepository {}
interface ProductCatalog {}
interface OrderRepository {}
interface CustomerRepository {}
interface ApprovalQueue {}
interface AuditLog {}
interface PolicyEngine {}
interface MediaRepository {}
interface UserDirectory {}
interface TicketRepository {}
```

Adapters can implement these interfaces for:

* Custom CMSs
* Laravel apps
* Symfony apps
* Magento
* Drupal
* WordPress
* Statamic
* Internal ecommerce systems
* Legacy PHP platforms

Purple PHP should be domain-capable, not domain-captive.

## 12. MVP Scope

The final product should include smart functions, chatbots, looping agents, hooks, and a CLI.

The first MVP milestone should be narrower: prove smart functions and the foundations they require. This gives Purple PHP a useful Composer-first product surface while forcing the core provider, schema, policy, audit, and secret-handling contracts to exist.

### MVP Milestone 1: Smart Functions

MVP 1 features:

* Composer package
* Provider abstraction
* OpenAI provider
* Environment secret resolver
* Smart function runner
* JSON schema input/output validation
* Prompt template support
* Retry and error handling
* Basic policy checks
* Basic audit log
* File-based or SQLite audit store
* Fake provider for tests
* Test utilities

MVP 1 should deliberately defer:

* Chat sessions
* Looping agents
* Runtime hooks
* Full CLI runner
* Native runtime
* CMS and ecommerce adapters

### MVP 1 Example Workflows

1. Classify product descriptions by risk level.
2. Generate structured metadata for catalog entries.
3. Extract entities from support tickets.
4. Normalize messy product attributes into a typed DTO.
5. Summarize content with an auditable provider call and cost record.

## 13. Enterprise Roadmap

Current status as of 2026-06-04: Phases 1, 2, 3, 4, and 5 are implemented in the Composer-first SDK and covered by the local validation suite. Phase 5 is represented as optional native/runtime readiness contracts; Composer mode remains the stable adoption baseline.

Phase 5.1 extends the runtime-continuation path without reversing that boundary. Native acceptance, sidecar resume, package-split planning, and baseline guardrails should be executable through Composer-safe tests, fake providers, injectable transports, and ignored local runtime state.

### Phase 1: SDK Foundation (Complete)

* Smart functions
* Provider abstraction
* Secrets
* Structured output
* Prompt templates
* Basic audit trail
* Basic policy model
* Fake provider and test helpers

### Phase 2: Chat, CLI, and Tool Foundations (Complete)

* Chat sessions
* Chunkable chat responses for streaming-compatible consumers
* Schema-validated PHP tool contracts
* Role-aware sessions
* Approval queue interface
* CLI runner for local smart-function, chat, and agent demos

### Phase 3: Looping Agents and Hooks (Complete)

* Agent loop
* Budgets
* Step limits
* Run state
* Provider and tool retry rules
* Provider, tool, validation, policy, and budget failure recovery
* Replayable tool logs
* Approval workflows
* Runtime hook system v1

### Phase 4: Enterprise Hardening (Complete)

* Tenant-aware policy metadata
* Provider-route and data-residency policy checks
* PII redaction before provider requests
* Redacted replay logs and audit exports
* Context-aware, Vault, and brokered cloud-secret resolvers
* JSONL audit exporter for SIEM/log-pipeline handoff
* Azure/Bedrock providers
* Sidecar provider brokerage
* Composable enterprise policy engine and rule set
* Webhook observability exporter

### Phase 5: Native Runtime (Complete)

* Optional PHP extension/native bridge contract
* Versioned sidecar protocol
* Sandboxed tool execution
* Durable agent run store
* Runtime metrics and performance profiling surface
* On-prem/native readiness story
* Composer mode preserved as the stable baseline

## 14. Design Principles

Purple PHP should be:

* Composer-first
* Enterprise-safe
* Provider-neutral
* CMS-agnostic
* Ecommerce-aware
* Auditable by default
* Policy-driven
* Hookable without becoming chaotic
* Useful in legacy systems
* Friendly to real PHP developers
* Optional-native, not native-required

The product should treat legacy PHP estates as terrain, not trash.

## 15. Immediate Next Steps

Active mission: `kitty-specs/runtime-continuation-mega-mission-01KTA3AD/`

This Phase 5.1 mega-mission packages the next runtime-continuation work into four reviewable work packages:

1. Native extension acceptance boundary.
2. Sidecar durable-run resume contract.
3. Enterprise adapter split decision.
4. Composer baseline guardrails.

Composer mode remains the stable adoption baseline while native and sidecar runtime work stays optional.

WP04 closes this slice by proving the default SDK path still validates without native extensions, sidecar services, cloud SDK packages, or live network services.
