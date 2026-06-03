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
- optional tool side-effect level

Policy engines can inspect this metadata before provider requests or tool calls. Audit exporters can attach the same metadata to SIEM, warehouse, or observability pipelines.

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
  "occurred_at": "2026-06-03T00:00:00+00:00",
  "payload": {}
}
```

The shape is intentionally plain JSON so it can be forwarded to SIEM, log pipelines, message queues, or audit stores.

## Deployment Readiness

Pure Composer mode is the adoption baseline. Sidecar and native extension profiles are readiness contracts, not mandatory runtime requirements.

- Composer mode: PHP 8.2+, Composer autoload, no native install.
- Sidecar mode: optional provider brokerage, observability export, and centralized policy coordination.
- Native extension mode: optional platform-team installation for lower-latency or stronger local runtime boundaries.

Vault, cloud-secret, observability, and SIEM integrations should be adapter surfaces around the Composer SDK unless a customer explicitly chooses sidecar or native mode.
