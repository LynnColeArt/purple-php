# ADR 001: Enterprise Adapter Split

## Status

Accepted for planning. Defer physical package extraction to a later packaging mission.

## Context

Purple PHP is a Composer-first SDK. Provider, security, audit, domain workflow, sidecar, and native runtime surfaces must remain useful from the core package without requiring native extensions, running sidecars, cloud SDKs, or network services during normal validation.

The current repository now contains enough enterprise adapter surfaces to make package boundaries explicit before any source tree split happens. This adapter split decision records the first package split candidate and the boundary that future extraction work should preserve.

## Decision

The first enterprise adapter package split should be `purple-php/provider-bedrock`.

This package should own the AWS Bedrock runtime provider adapter and any future Bedrock-specific authentication, signing, endpoint routing, region handling, retry classification, and integration tests. The core SDK should keep the provider contracts, provider request/response value objects, policy/audit contracts, fake provider, and Composer-first SDK entry point.

Physical extraction should not happen in this mission. The next packaging step is a dedicated package split mission that creates the package skeleton, proves cross-package tests, and updates SDK convenience factories without weakening the core Composer install path.

## Candidate Inventory

| Surface | Current Files | Split Candidate | Decision |
| --- | --- | --- | --- |
| Provider adapters | `src/Provider/OpenAI/**`, `src/Provider/Azure/**`, `src/Provider/Bedrock/**`, `src/Provider/Sidecar/**`, provider tests, related SDK factories | `purple-php/provider-bedrock`, then `purple-php/provider-azure`, `purple-php/provider-openai`, and `purple-php/provider-sidecar` | Split Bedrock first. It is the clearest enterprise cloud boundary and the most likely to grow optional AWS-specific dependency or signing behavior. |
| Domain workflow ports | `src/Domain/Workflow/**`, `src/Domain/InMemory/**`, `src/Domain/EnterpriseContext.php`, domain examples and tests | `purple-php/domain-workflows` or platform-specific CMS/ecommerce adapter packages | Keep workflow ports and DTOs in core for now. Concrete CMS/ecommerce adapters can split after there is a real platform adapter beyond the in-memory fixture. |
| Sidecar and native runtime | `src/Runtime/Sidecar/**`, `src/Runtime/Durable/**`, `src/Runtime/PhpExtensionBridge.php`, runtime tests and examples | `purple-php/runtime-sidecar`, `purple-php/native` | Defer. Phase 5.1 is still defining contract compatibility, so splitting now would freeze unstable runtime package boundaries too early. |
| Security resolvers | `src/Security/VaultSecretResolver.php`, `src/Security/CloudSecretResolver.php`, `src/Security/ContextualSecretResolver.php`, resolver contracts and tests | `purple-php/secrets-vault`, `purple-php/secrets-cloud` | Defer until resolver implementations need provider SDKs or tenant-specific backend clients. Core should retain contracts and environment/contextual composition. |
| Audit exporters | `src/Audit/FileAuditExporter.php`, `src/Audit/WebhookAuditExporter.php`, `src/Domain/Audit/AuditExportRecord.php`, audit tests | `purple-php/audit-exporters` or specific SIEM exporters | Defer. The current exporters are small, dependency-light, and help prove enterprise audit behavior inside Composer mode. |

## Package Boundary

`purple-php/provider-bedrock` should depend on the core SDK package and provide the Bedrock implementation behind existing provider contracts.

Likely future movement:

- Move `src/Provider/Bedrock/BedrockProvider.php` to the provider package.
- Move `tests/Provider/Bedrock/BedrockProviderTest.php` to the provider package.
- Move future Bedrock signing, credential-source, endpoint, and fixture tests to the provider package.
- Keep `src/Contracts/Provider/**`, `src/ProviderProfile.php`, `src/Sdk.php`, `src/Policy/**`, and `src/Audit/**` in the core package unless a later mission designs an extension-registration API.

Core SDK responsibilities after the split:

- Define `Provider`, `ProviderRequest`, `ProviderResponse`, and `ProviderUsage`.
- Preserve fake-provider tests and examples without cloud network calls.
- Keep policy, audit, hooks, smart functions, chat, agents, domain workflow ports, and runtime contracts Composer-first.
- Avoid requiring Bedrock, AWS SDK, sidecar, or native extension dependencies for `composer install` or `composer check`.

Provider package responsibilities after the split:

- Construct Bedrock requests from core `ProviderRequest` values.
- Normalize Bedrock responses into core `ProviderResponse` values.
- Own Bedrock-specific endpoint, region, signing, authentication, retry, and fixture behavior.
- Provide package-local tests that run without live AWS calls by default.

## Rationale

Bedrock is the right first package split because it is enterprise-shaped but not core-shaped. It has a distinct cloud provider boundary, region and runtime endpoint concerns, and a natural path toward optional AWS dependencies. Extracting it first protects the Composer-first SDK from cloud-specific dependency growth while preserving provider-neutral core behavior.

Provider adapters are also easier to split safely than runtime contracts. The provider contract is already stable, while sidecar and optional-native work is still being made executable through Phase 5.1. Domain workflow adapters should wait until Purple PHP has at least one real CMS or ecommerce adapter package; splitting only the in-memory fixture would create package churn without product value.

## Deferred Extraction

Do not move files immediately.

The package split is deferred because this mission is proving Phase 5.1 runtime continuation and Composer baseline guardrails. Moving Bedrock now would require package metadata, path repositories or split repositories, updated SDK factory wiring, CI changes, and release notes. Those concerns deserve their own mission with package-level acceptance checks.

The later package split mission should include:

- A `purple-php/provider-bedrock` Composer package skeleton.
- A compatibility test proving the package implements the core `Provider` contract.
- A decision on whether `Sdk::bedrock()` remains in core, moves to the provider package, or delegates through an optional factory registration surface.
- A default validation path that runs without AWS credentials or live network services.
- Documentation that the provider package is optional and not required for core Composer-first adoption.

## Roadmap Alignment

The next packaging step is no longer an implicit broad refactor. It is:

1. Preserve Composer-first core validation through WP04.
2. Open a dedicated package split mission for `purple-php/provider-bedrock`.
3. Extract the Bedrock adapter only after package-level tests prove the core SDK still installs and validates without optional cloud, sidecar, or native dependencies.
4. Revisit Azure, OpenAI, sidecar provider, secrets, audit exporters, and real CMS/ecommerce adapters after the Bedrock package split establishes the repeatable pattern.

This keeps optional-native and optional-sidecar runtime work separate from provider package extraction. Runtime continuation remains a contract track; provider package extraction becomes a packaging track.
