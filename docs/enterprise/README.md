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

Composer mode now includes testable provider adapters for OpenAI, Azure OpenAI, Bedrock runtime, and a brokered sidecar provider. The Bedrock and sidecar paths keep signing, routing, and platform-specific transport in injectable transports so the core SDK does not require cloud SDK dependencies.

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
- `SidecarProtocol` defines a versioned JSON envelope for private runtime communication.
- `SandboxedToolExecutor` enforces side-effect, payload-size, and duration limits around PHP tools.
- `DurableRunStore` and `FileDurableRunStore` provide a persistence contract for pausable or replayable agent runs.
- `RuntimeMetrics` captures duration and memory deltas for runtime profiling.

Composer mode remains the stable baseline; these surfaces let on-prem or managed-infrastructure customers adopt sidecar/native runtime pieces deliberately.

The runtime handoff example at `examples/runtime/durable-sidecar-handoff.php` shows a Composer-mode agent run being saved through the durable run store and wrapped in a versioned sidecar envelope for later sidecar orchestration.

## Composer Baseline Guardrail

Phase 5.1 makes runtime continuation more executable without changing the adoption baseline. Native acceptance checks must run through PHP-level contract fixtures unless a platform team explicitly installs a compatible native runtime. Sidecar resume and handoff examples must use fake or injectable transports by default and write generated run state under ignored `var/runtime/` paths.

`composer check` remains the baseline validation command. It must not require native extensions, sidecar services, cloud SDK packages, AWS credentials, Vault credentials, or live network access.
