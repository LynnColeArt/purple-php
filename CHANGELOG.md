# Changelog

All notable changes to `purple-php/sdk` are recorded here.

## 0.1.0 - 2026-06-05

First public GitHub release for the Composer-first Purple PHP SDK.

### Added

- Smart functions, chat sessions, looping agents, tool contracts, policy checks, audit logging, retry behavior, structured output validation, and CLI diagnostics.
- Enterprise readiness surfaces for tenant policy, data residency, secret lookup, observability export, approval flow evidence, runtime hooks, durable run state, and sidecar/native readiness contracts.
- Optional provider architecture with Bedrock extracted into the standalone `purple-php/provider-bedrock` package line.
- Composer-safe runtime prototypes for sidecar resume and native compatibility checks.

### Validation

Release validation:

```bash
composer validate --strict
composer check
```
