# Purple PHP Runtime Charter

## Project Intent

Purple PHP is an enterprise AI modernization SDK for existing PHP applications. It should help teams add secure, auditable AI behavior to PHP-based CMS, ecommerce, admin, support, catalog, and workflow systems without rewriting their application estate.

The SDK is Composer-first. Sidecar, native runtime, and PHP extension deployment modes may be added later, but pure Composer adoption is the baseline.

MVP 1 is smart-functions-only. Chatbots, looping agents, runtime hooks, CLI, sidecar, native runtime, and domain adapters remain long-horizon product surfaces, but they must not be forced into WP01 or WP02.

## Testing And Quality

Testing is required for implementation work.

```yaml
testing:
  framework: phpunit
  type_checking: phpstan
  tdd_required: true
quality:
  linting: php-cs-fixer
  pr_approvals: 1
commits:
  convention: conventional
```

Implementation work should include focused unit tests for public contracts and runtime behavior. Provider integrations must be testable without real network calls by default.

## Branching

```yaml
branch_strategy:
  main_branch: main
  rules:
    - work packages run in Spec Kitty lanes or worktrees
    - completed changes merge back to main unless the human redirects the target
```

## Doctrine

```yaml
doctrine:
  selected_paradigms:
    - test-first
  selected_directives:
    - specification-fidelity
    - locality-of-change
    - test-and-typecheck-quality-gate
    - 039-lynn-cole-engineering-culture
  available_tools:
    - composer
    - phpunit
    - phpstan
    - php-cs-fixer
```

## Directives

1. Policy is enforcement. Hooks are extension points. Core security checks must not depend on optional hooks.
2. Direct provider connections are the canonical MVP inference path. MCP may be supported later as a provider adapter, but it must not become the core model abstraction.
3. Never encourage raw provider API keys in application code. Secrets must flow through secret resolver abstractions and must be redacted from logs and audit records.
4. Keep Purple PHP CMS-agnostic and ecommerce-aware. Domain ports should model workflows rather than binding the core SDK to one platform.
5. Native runtime and sidecar support must remain optional. Composer mode is the adoption baseline.
6. Keep MVP 1 scoped to smart functions plus provider, secret, schema, policy, audit, prompt, fake-provider, and test foundations.
