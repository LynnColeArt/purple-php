# Purple PHP Implementation Plan

## Strategy

Use a Composer-first PHP monorepo. Start with a small SDK package and avoid early package fragmentation. The first milestone should prove smart functions end to end while laying down contracts that future chatbots, tools, agents, hooks, and CLI can reuse.

The product should be built in large Spec Kitty work packages:

1. SDK substrate and contracts.
2. Smart-functions MVP vertical slice.
3. Chat, tool contracts, and CLI.
4. Looping agents, approvals, and runtime hooks.
5. Enterprise hardening, domain ports, and deployment-mode readiness.

## Architecture

Core namespaces should remain simple at first:

```text
Purple\
Purple\Provider\
Purple\Security\
Purple\Policy\
Purple\Audit\
Purple\Schema\
Purple\Prompt\
Purple\FunctionRunner\
```

Avoid committing to too many Composer packages until the core interfaces stabilize. A likely first structure is:

```text
composer.json
src/
tests/
examples/
docs/
```

Provider packages, CMS/ecommerce ports, native runtime, and sidecar packages can split later.

## Smart-Function MVP

The MVP should expose a fluent API similar to:

```php
$summary = Purple::function('summarize_article')
    ->using($provider)
    ->withPrompt($prompt)
    ->input($article)
    ->returns(Summary::class)
    ->run();
```

The exact API may change during implementation, but it must preserve these ideas: named function, provider, prompt/template, typed input, typed/validated output, policy check, audit record, and testability through a fake provider.

## Security Model

Policy is enforcement. Hooks are extension points.

MVP policy should enforce allowed provider/model and budget/run limits. Later policy should cover tool permissions, user permissions, data field sensitivity, approval requirements, tenant isolation, provider routing, and data residency.

Secrets should flow through `SecretStore` or `SecretResolver` abstractions. Raw secrets must not be logged. Audit records must redact sensitive values.

## Audit Model

Audit events should be append-only and suitable for debugging. MVP events should cover run start/completion/failure, provider request/response metadata, validation result, policy decisions, and cost metadata when the provider returns it.

File-based audit is acceptable for MVP. SQLite is acceptable if it remains easy to inspect and test.

## Testing Model

The fake provider is essential. Tests should validate framework behavior without external API calls. OpenAI provider tests should avoid real network calls unless explicitly marked integration.

Minimum verification for MVP:

```bash
composer test
composer analyse
composer lint
```

Exact commands may change based on the chosen PHP tooling.

## Long-Horizon Design Notes

Chat should reuse provider, policy, audit, schema, and secret contracts.

Tools should be typed PHP capabilities with explicit side-effect levels:

```text
read_only
draft_only
writes_pending_approval
writes_directly
destructive
external_side_effect
```

Looping agents should never bypass policy checks. Each step and tool call should be auditable and bounded by budget/step/time limits.

Runtime hooks should focus first on provider, tool, approval, run, step, policy, and budget events. Command/file hooks should be reserved for sidecar/native/devops contexts.

Native runtime should improve isolation, durability, streaming, and performance, but Composer mode must remain the canonical baseline.

## Risks

Risk: MVP grows into chat/agent/hooks too early.

Mitigation: MVP 1 acceptance is smart-functions-only.

Risk: Hooks become a security bypass.

Mitigation: core policy always runs in the execution path and cannot depend on optional hooks.

Risk: Package fragmentation slows implementation.

Mitigation: start with one SDK package and split after interfaces stabilize.

Risk: Domain abstractions become too generic to implement.

Mitigation: define workflow ports around concrete actions, not broad enterprise nouns.

