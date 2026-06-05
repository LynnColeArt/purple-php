# ADR 001: Enterprise Adapter Split

## Status

Accepted and implemented as a local monorepo package split for Bedrock.

## Context

Purple PHP is a Composer-first SDK. Provider, security, audit, domain workflow, sidecar, and native runtime surfaces must remain useful from the core package without requiring native extensions, running sidecars, cloud SDKs, or network services during normal validation.

The repository contains enough enterprise adapter surfaces to make package boundaries explicit without turning core Composer validation into a cloud-provider install. This adapter split decision records the first package split and the boundary that future provider packages should preserve.

## Decision

The first enterprise adapter package split is `purple-php/provider-bedrock`.

This package should own the AWS Bedrock runtime provider adapter and any future Bedrock-specific authentication, signing, endpoint routing, region handling, retry classification, and integration tests. The core SDK should keep the provider contracts, provider request/response value objects, policy/audit contracts, fake provider, and Composer-first SDK entry point.

The package exists in this monorepo at `packages/provider-bedrock`. It has its own Composer metadata, package-local test/static-analysis/fixer configuration, PSR-4 autoloading for `Purple\Provider\Bedrock\`, and a path repository back to the root `purple-php/sdk` package for local validation. It is not yet a separate repository or Packagist publication.

## Candidate Inventory

| Surface | Current Files | Split Candidate | Decision |
| --- | --- | --- | --- |
| Provider adapters | `src/Provider/OpenAI/**`, `src/Provider/Azure/**`, `packages/provider-bedrock/**`, `src/Provider/Sidecar/**`, provider tests, related SDK factories | `purple-php/provider-bedrock`, then `purple-php/provider-azure`, `purple-php/provider-openai`, and `purple-php/provider-sidecar` | Bedrock is split first. It is the clearest enterprise cloud boundary and the most likely to grow optional AWS-specific dependency or signing behavior. |
| Domain workflow ports | `src/Domain/Workflow/**`, `src/Domain/InMemory/**`, `src/Domain/EnterpriseContext.php`, domain examples and tests | `purple-php/domain-workflows` or platform-specific CMS/ecommerce adapter packages | Keep workflow ports and DTOs in core for now. Concrete CMS/ecommerce adapters can split after there is a real platform adapter beyond the in-memory fixture. |
| Sidecar and native runtime | `src/Runtime/Sidecar/**`, `src/Runtime/Durable/**`, `src/Runtime/PhpExtensionBridge.php`, runtime tests and examples | `purple-php/runtime-sidecar`, `purple-php/native` | Defer. Phase 5.1 is still defining contract compatibility, so splitting now would freeze unstable runtime package boundaries too early. |
| Security resolvers | `src/Security/VaultSecretResolver.php`, `src/Security/CloudSecretResolver.php`, `src/Security/ContextualSecretResolver.php`, resolver contracts and tests | `purple-php/secrets-vault`, `purple-php/secrets-cloud` | Defer until resolver implementations need provider SDKs or tenant-specific backend clients. Core should retain contracts and environment/contextual composition. |
| Audit exporters | `src/Audit/FileAuditExporter.php`, `src/Audit/WebhookAuditExporter.php`, `src/Domain/Audit/AuditExportRecord.php`, audit tests | `purple-php/audit-exporters` or specific SIEM exporters | Defer. The current exporters are small, dependency-light, and help prove enterprise audit behavior inside Composer mode. |

## Package Boundary

`purple-php/provider-bedrock` depends on the core SDK package and provides the Bedrock implementation behind existing provider contracts.

Implemented movement:

- `src/Provider/Bedrock/BedrockProvider.php` moved to `packages/provider-bedrock/src/BedrockProvider.php`.
- `tests/Provider/Bedrock/BedrockProviderTest.php` moved to `packages/provider-bedrock/tests/BedrockProviderTest.php`.
- `Purple\Provider\Bedrock\BedrockSdk::create()` provides the package-local construction path through `Sdk::fromProvider()`.
- Bedrock package tests use injectable transports and fixtures, not AWS credentials or live network calls.

Future movement remains package-local:

- Add real Bedrock signing, credential-source, endpoint, retry, and fixture behavior to the provider package.
- Add AWS SDK integration only as an explicit provider-package dependency if a later mission chooses that surface.
- Keep `src/Contracts/Provider/**`, `src/ProviderProfile.php`, `src/Sdk.php`, `src/Policy/**`, and `src/Audit/**` in the core package unless a later mission designs an extension-registration API.

Core SDK responsibilities after the split:

- Define `Provider`, `ProviderRequest`, `ProviderResponse`, and `ProviderUsage`.
- Preserve fake-provider tests and examples without cloud network calls.
- Keep policy, audit, hooks, smart functions, chat, agents, domain workflow ports, and runtime contracts Composer-first.
- Avoid requiring Bedrock, AWS SDK, sidecar, or native extension dependencies for root `composer install` or `composer check`.
- Keep `ProviderProfile::bedrock()` as provider-neutral profile metadata.
- Keep `Sdk::fromProvider()` as the composition point for optional provider packages.
- Do not import `Purple\Provider\Bedrock\BedrockProvider` or expose a root `Sdk::bedrock()` factory that requires the optional package at autoload time.

Provider package responsibilities after the split:

- Construct Bedrock requests from core `ProviderRequest` values.
- Normalize Bedrock responses into core `ProviderResponse` values.
- Own Bedrock-specific endpoint, region, signing, authentication, retry, and fixture behavior.
- Provide package-local tests that run without live AWS calls by default.
- Provide the ergonomic Bedrock SDK construction helper for package adopters.

## Rationale

Bedrock is the right first package split because it is enterprise-shaped but not core-shaped. It has a distinct cloud provider boundary, region and runtime endpoint concerns, and a natural path toward optional AWS dependencies. Extracting it first protects the Composer-first SDK from cloud-specific dependency growth while preserving provider-neutral core behavior.

Provider adapters are also easier to split safely than runtime contracts. The provider contract is already stable, while sidecar and optional-native work is still being made executable through Phase 5.1. Domain workflow adapters should wait until Purple PHP has at least one real CMS or ecommerce adapter package; splitting only the in-memory fixture would create package churn without product value.

## Extraction Record

The Bedrock extraction is implemented as a local monorepo package split.

The package split includes:

- A `purple-php/provider-bedrock` Composer package skeleton.
- Package-local `BedrockProvider` source and tests.
- A package-local `BedrockSdk` helper that replaces root `Sdk::bedrock()` usage.
- Root `Sdk.php` decoupling from `Purple\Provider\Bedrock\BedrockProvider`.
- Root and provider-package validation evidence captured in `kitty-specs/bedrock-provider-package-split-01KTAHKT/acceptance-matrix.json`.

The remaining deferred work is packaging and production transport maturity: Packagist publication, release automation, real AWS signing or SDK integration, credential discovery, live integration tests, and any separate-repository split.

## Roadmap Alignment

The packaging track is no longer an implicit broad refactor. Its first milestone is now complete in the monorepo:

1. Preserve Composer-first core validation without requiring Bedrock.
2. Keep `purple-php/provider-bedrock` as an optional monorepo package with package-local validation.
3. Use the Bedrock package split as the repeatable pattern for future provider packages.
4. Revisit Azure, OpenAI, sidecar provider, secrets, audit exporters, and real CMS/ecommerce adapters after this package boundary has release and publication follow-through.

This keeps optional-native and optional-sidecar runtime work separate from provider package extraction. Runtime continuation remains a contract track; provider package extraction becomes a packaging track.
