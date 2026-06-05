# Purple PHP Enterprise Integration

Purple PHP is an enterprise PHP AI modernization SDK. The core product remains CMS-agnostic and ecommerce-aware: domain adapters model workflows, not a specific platform brand.

## Domain Ports

The domain layer exposes action-oriented ports:

- `ContentWorkflowPort`: search content and draft content revisions.
- `CatalogWorkflowPort`: search catalog records and draft catalog updates.
- `OrderWorkflowPort`: look up order summaries.
- `SupportWorkflowPort`: classify support tickets.
- `ApprovalWorkflowPort`: request human or system approval.
- `ExternalAuditPort`: record export-ready audit events.

Adapters can wrap an in-house CMS, ecommerce platform, support desk, admin system, or approval system without changing the SDK identity.

## Enterprise Context

`EnterpriseContext` carries policy and audit metadata:

- `tenant_id`
- `user_id`
- `data_sensitivity`
- `retention_days`
- `provider_route`
- `data_residency_region`
- optional tool side-effect level

Policy engines can inspect this metadata before provider requests or tool calls. Audit exporters can attach the same metadata to SIEM, warehouse, or observability pipelines.

## Policy and Redaction

`BasicPolicyEngine` can enforce allowed tenants, provider routes, and data residency regions alongside provider, model, run, and cost limits.

`EnterprisePolicyEngine` composes a base policy with rule objects. The current rule set includes restricted-data route enforcement, write/external side-effect approval metadata, and retention-limit checks.

`PiiRedactor` provides a default recursive redactor for common email, phone, SSN, and card-number shapes. Smart functions, chat sessions, and agent provider requests can receive a redactor so protected data is masked before provider calls. Agent replay logs and file audit exports also use the redactor when configured.

## Contextual Secrets

`EnterpriseSecretResolver` is the contract for Vault, cloud-secret, or tenant-aware secret adapters. `ContextualSecretResolver` wraps an ordinary `SecretResolver` and prefers tenant-specific environment variable names such as `TENANT_A_OPENAI_API_KEY` before falling back to `OPENAI_API_KEY`.

`VaultSecretResolver` supports Vault KV-style lookups, while `CloudSecretResolver` supports brokered cloud-secret endpoints such as AWS Secrets Manager, Azure Key Vault, or GCP Secret Manager wrappers.

## Provider and Sidecar Adapters

Composer mode includes testable core adapters for OpenAI, Azure OpenAI, and a brokered sidecar provider. AWS Bedrock now lives in the optional monorepo package `purple-php/provider-bedrock` at `packages/provider-bedrock`, so the root SDK keeps provider contracts and `Sdk::fromProvider()` without importing or constructing the Bedrock adapter.

The Bedrock package provides `Purple\Provider\Bedrock\BedrockProvider` plus `Purple\Provider\Bedrock\BedrockSdk::create()` for teams that opt into the package:

```php
use Purple\Provider\Bedrock\BedrockSdk;
use Purple\ProviderProfile;

$sdk = BedrockSdk::create(
    profile: ProviderProfile::bedrock(model: 'anthropic.model'),
    region: 'us-east-1',
);
```

Local validation is package-scoped:

```bash
composer check --working-dir=packages/provider-bedrock
```

This package is a local monorepo package boundary, not a Packagist publication promise. It still uses injectable transports by default; live AWS signing, credential discovery, AWS SDK integration, and production Bedrock transport policy are future opt-in surfaces.

## Audit Export Shape

`AuditExportRecord::toExportPayload()` returns:

```json
{
  "event_type": "agent.tool.completed",
  "run_id": "run-123",
  "tenant_id": "tenant-a",
  "user_id": "user-42",
  "data_sensitivity": "internal",
  "retention_days": 30,
  "provider_route": "default",
  "data_residency_region": "us",
  "occurred_at": "2026-06-03T00:00:00+00:00",
  "payload": {}
}
```

The shape is intentionally plain JSON so it can be forwarded to SIEM, log pipelines, message queues, or audit stores. `FileAuditExporter` writes this shape as JSONL and can apply a `DataRedactor` before export. `WebhookAuditExporter` posts the same export payload to observability or SIEM ingestion endpoints.

## Deployment Readiness

Pure Composer mode is the adoption baseline. Sidecar and native extension profiles are readiness contracts, not mandatory runtime requirements.

- Composer mode: PHP 8.2+, Composer autoload, no native install.
- Sidecar mode: optional provider brokerage, observability export, centralized policy coordination, brokered secret/provider routing, versioned sidecar envelopes, sandboxed tool execution, durable runs, and runtime metrics.
- Native extension mode: optional platform-team installation for lower-latency or stronger local runtime boundaries through the `NativeRuntime` bridge contract.

Vault, cloud-secret, observability, and SIEM integrations should be adapter surfaces around the Composer SDK unless a customer explicitly chooses sidecar or native mode.

## Native/Runtime Readiness

Phase 5 keeps native work optional while making the contract concrete:

- `PhpExtensionBridge` calls an injected native invoker or compatible `purple_native_invoke` function and normalizes native results.
- `NativeRuntimeCompatibility` runs the acceptance ping and returns a structured `compatible`, `incompatible`, or `unavailable` report.
- `SidecarProtocol` defines a versioned JSON envelope for private runtime communication.
- `SandboxedToolExecutor` enforces side-effect, payload-size, and duration limits around PHP tools.
- `DurableRunStore` and `FileDurableRunStore` provide a persistence contract for pausable or replayable agent runs.
- `RuntimeMetrics` captures duration and memory deltas for runtime profiling.

Composer mode remains the stable baseline; these surfaces let on-prem or managed-infrastructure customers adopt sidecar/native runtime pieces deliberately.

The runtime handoff example at `examples/runtime/durable-sidecar-handoff.php` shows a Composer-mode agent run being saved through the durable run store and wrapped in a versioned sidecar envelope for later sidecar orchestration.

The sidecar runtime service prototype adds a local service boundary for durable resume. `SidecarRuntimeService` handles encoded `purple.sidecar.v1` resume envelopes against a `DurableRunStore`, and `bin/purple sidecar resume <run-store-dir> <run-id> [node-id]` exercises that path without starting a daemon.

The native extension compatibility prototype adds `bin/purple native check fixture` and `bin/purple native check extension [extension-name]`. Fixture mode proves the native acceptance contract through Composer-safe PHP code; extension mode reports `unavailable` unless a platform team has deliberately installed a compatible native extension.

## Composer Baseline Guardrail

Phase 5.1 makes runtime continuation more executable without changing the adoption baseline. Native acceptance checks must run through PHP-level contract fixtures unless a platform team explicitly installs a compatible native runtime. Sidecar resume and handoff examples must use fake or injectable transports by default and write generated run state under ignored `var/runtime/` paths. Phase 5.4 keeps the same guardrail: the sidecar runtime service prototype is local and opt-in, not a required process for SDK installation or validation. The native compatibility prototype keeps the same line: fixture checks are Composer-safe, and extension checks are explicit opt-in diagnostics.

`composer check` remains the core baseline validation command. It must not require native extensions, sidecar services, optional provider packages, cloud SDK packages, AWS credentials, Vault credentials, or live network access.

## Provider Package Release Readiness

The Bedrock provider package is published as a standalone GitHub release at `LynnColeArt/purple-php-provider-bedrock`. The release-readiness track documents the `purple-php/provider-bedrock` `0.1.x` package line, package-local changelog, Packagist handoff checklist, and CI validation.

Provider package validation is separate from the core Composer baseline:

```bash
composer check
composer check --working-dir=packages/provider-bedrock
```

The provider package job must not require AWS credentials, live Bedrock calls, AWS SDK packages, sidecar services, or native extensions. Future publication, live integration tests, and real AWS signing remain explicit opt-in work.
