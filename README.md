# Purple PHP

![Purple PHP hero graphic](hero.png)

Purple PHP is an enterprise AI modernization SDK for existing PHP applications.

It is designed for teams with significant sunk cost in PHP-based CMS, ecommerce, admin, support, catalog, and workflow systems. The goal is to add secure, auditable AI behavior without rewriting the application estate.

## Current Shape

Purple PHP is Composer-first. Native runtime, sidecar, and PHP extension support may become important deployment modes later, but the developer-facing center of gravity is PHP.

The current SDK includes:

* Smart functions: typed, auditable AI calls for narrow repeatable tasks.
* Chat sessions: stateful assistant flows with role-aware history and chunkable responses.
* Tool contracts: named, schema-validated tools with input/output schemas and side-effect levels.
* Looping agents: goal-driven runners that can call tools under limits, hooks, policy, audit, retries, replay logs, and approvals.
* Runtime hooks: extension points around provider requests, tool calls, and agent lifecycle events.
* CLI support: a `purple` command for demos, diagnostics, provider checks, sidecar resume prototyping, and audit inspection.
* Enterprise workflow ports: CMS-agnostic content, catalog, order, support, approval, and audit adapters.
* Enterprise hardening: tenant/data-residency policy metadata, advanced policy rules, PII redaction, contextual/Vault/cloud secret lookup, Azure and sidecar providers, optional Bedrock provider package, and SIEM/observability export.
* Optional runtime readiness: native bridge contracts, sidecar envelopes, sandboxed tool execution, durable run storage, runtime metrics, and on-prem deployment metadata.

The first milestone was smart functions. Phases 2, 3, 4, and 5 are now represented in the Composer-first SDK as chat, CLI, tools, looping agents, approvals, runtime hooks, retry behavior, run state, replayable tool logs, enterprise policy, secret adapters, cloud provider adapters, sidecar brokerage, observability export, and optional native/runtime readiness contracts.

Phase 5.1 makes that runtime work executable as Composer-safe contracts: native acceptance, sidecar resume, and package-split decisions stay testable through PHP fixtures, fake providers, injectable transports, and ignored local runtime paths. The Bedrock package split applies that same baseline to enterprise providers: a normal core Composer install still does not require Bedrock, a native extension, a sidecar process, cloud SDK dependencies, or live network services.

Phase 5.3 makes the Bedrock provider package release-ready without publishing it yet: package docs, first-release notes, release checklist, and CI validation now describe how `purple-php/provider-bedrock` can ship later while preserving the Composer-first baseline.

Phase 5.4 adds a local sidecar runtime service prototype for durable resume. It accepts the same `purple.sidecar.v1` resume envelope used by the PHP contract tests, reads a local durable run store, and returns deterministic accepted/rejected response envelopes without requiring a sidecar daemon, socket listener, native extension, cloud SDK, or live network service.

Phase 5.5 adds a native extension compatibility prototype. `NativeRuntimeCompatibility` runs the native acceptance ping against any `NativeRuntime`, `bin/purple native check fixture` proves the path with Composer-safe PHP, and `bin/purple native check extension [extension-name]` lets platform teams check an installed extension deliberately.

## Quick Start

```bash
composer install
php examples/smart-functions/sdk-quickstart.php
php examples/smart-functions/catalog-summary.php
php examples/chat/fake-chat.php
php examples/agents/catalog-agent.php
php examples/runtime/durable-sidecar-handoff.php
php examples/runtime/durable-sidecar-resume.php
php bin/purple sidecar resume var/runtime/runs run-resume-example
php bin/purple native check fixture
php bin/purple demo smart-function
php bin/purple demo chat
php bin/purple demo agent
```

The `Sdk` entry point bundles a provider, model, policy, audit log, and schema validator for common setup:

```php
use Purple\Sdk;
use Purple\Testing\FakeProvider;

$sdk = new Sdk(
    provider: FakeProvider::replying('{"summary":"Ready for catalog review."}'),
    providerName: 'fake',
    model: 'fake-model',
);

$summary = $sdk->smartFunction(
    name: 'catalog.summary',
    prompt: 'Summarize {{ title }} as JSON.',
    outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
)->run(['title' => 'Merino travel cardigan']);
```

For named provider setup, use `ProviderProfile` factories:

```php
use Purple\ProviderProfile;
use Purple\Sdk;

$sdk = Sdk::openAI(
    profile: ProviderProfile::openAI(
        model: 'gpt-4.1-mini',
        secretName: 'OPENAI_API_KEY',
    ),
);
```

When installed as a Composer dependency, the CLI is exposed as:

```bash
vendor/bin/purple diagnostics
vendor/bin/purple audit inspect var/audit/catalog.jsonl
vendor/bin/purple demo chat
vendor/bin/purple demo agent
vendor/bin/purple provider check openai
vendor/bin/purple sidecar resume var/runtime/runs run-resume-example
vendor/bin/purple native check fixture
```

## Provider Security

Provider credentials are resolved through `SecretResolver` implementations instead of being passed around as normal strings in application code. `EnvironmentSecretResolver` reads named environment variables, and `SecretValue` redacts itself when stringified.

The OpenAI profile defaults to `OPENAI_API_KEY`, and `.env.example` documents the expected local setup without including secret values.

```bash
cp .env.example .env
vendor/bin/purple provider check openai
```

Tests and local examples can use `FakeProvider` to avoid external provider calls.

## Optional Bedrock Provider

AWS Bedrock support lives in the optional monorepo package `purple-php/provider-bedrock` under [packages/provider-bedrock](packages/provider-bedrock/). It is not required by the root `purple-php/sdk` package and is not assumed to be published on Packagist yet.

Local monorepo validation uses the package working directory:

```bash
composer install --working-dir=packages/provider-bedrock
composer check --working-dir=packages/provider-bedrock
```

Applications that install the Bedrock package can use the package-local factory instead of the former root `Sdk::bedrock()` convenience method:

```php
use Purple\Provider\Bedrock\BedrockSdk;
use Purple\ProviderProfile;

$sdk = BedrockSdk::create(
    profile: ProviderProfile::bedrock(model: 'anthropic.model'),
    region: 'us-east-1',
);
```

The package currently uses injectable transports and fixtures for validation. Real AWS signing, credential discovery, AWS SDK integrations, and live Bedrock calls remain future opt-in work.

## Enterprise Principles

Purple PHP treats existing PHP estates as valuable terrain.

The SDK should be:

* Composer-first
* provider-neutral
* secure by default
* auditable by default
* policy-driven
* CMS-agnostic
* ecommerce-aware
* optional-native, not native-required

Runtime continuation is executable only where teams opt into it. Composer mode remains the default baseline for local development, tests, examples, and package adoption.

Policy is enforcement. Hooks are extension points.

## Validation

```bash
composer check
```

The check script runs PHPUnit, PHPStan, and php-cs-fixer in dry-run mode.

## Acknowledgements

Special thanks to Patrick Haley at Apex Systems, whose memorable recruiting style reminded me that finishing this project would be more effective than allowing him to waste my time.

## Planning Artifacts

The current project outline lives in [outline.md](outline.md).

Spec Kitty mission packages:

* Enterprise SDK foundation: [kitty-specs/001-purple-php-enterprise-ai-sdk](kitty-specs/001-purple-php-enterprise-ai-sdk/spec.md)
* Runtime continuation Phase 5.1: [kitty-specs/runtime-continuation-mega-mission-01KTA3AD](kitty-specs/runtime-continuation-mega-mission-01KTA3AD/spec.md)
* Bedrock provider package split: [kitty-specs/bedrock-provider-package-split-01KTAHKT](kitty-specs/bedrock-provider-package-split-01KTAHKT/spec.md)
* Bedrock provider release readiness: [kitty-specs/provider-bedrock-release-readiness-01ktan01-01KTAMNF](kitty-specs/provider-bedrock-release-readiness-01ktan01-01KTAMNF/spec.md)
* Sidecar runtime service prototype: [kitty-specs/sidecar-runtime-service-prototype-01KTAQ1M](kitty-specs/sidecar-runtime-service-prototype-01KTAQ1M/spec.md)
* Native extension compatibility prototype: [kitty-specs/native-extension-compatibility-prototype-01KTAS1B](kitty-specs/native-extension-compatibility-prototype-01KTAS1B/spec.md)
