# Bedrock Provider Package Split

Source roadmap: `outline.md` Phase 5.1 follow-up and `docs/architecture/001-enterprise-adapter-split.md`.

## Mission Goal

Extract the AWS Bedrock provider adapter into the first optional enterprise adapter package, `purple-php/provider-bedrock`, while preserving the core SDK as a Composer-first package that validates without AWS credentials, AWS SDK dependencies, live network services, sidecars, or native extensions.

The mission should establish the repeatable package-split pattern for future provider packages without broadening into Azure, OpenAI, sidecar, secrets, audit exporters, or runtime package extraction.

## Product Context

Purple PHP currently ships the Bedrock provider directly inside the core SDK:

- `src/Provider/Bedrock/BedrockProvider.php`
- `tests/Provider/Bedrock/BedrockProviderTest.php`
- `ProviderProfile::bedrock()`
- `Sdk::bedrock()`

ADR 001 selected Bedrock as the first provider package split because it is enterprise-shaped, cloud-specific, likely to grow optional AWS signing/credential behavior, and easier to extract safely than sidecar or native runtime surfaces.

After this mission, core SDK package installation and validation must remain provider-neutral. The Bedrock package may depend on the core SDK contracts, but the core SDK must not depend on the Bedrock package to run `composer install`, `composer check`, local examples, or default SDK tests.

## Target Users

Primary users:

- PHP teams that want the core Purple SDK without optional AWS provider code.
- Enterprise teams that need an AWS Bedrock adapter package they can opt into explicitly.
- Maintainers who need a proven pattern for future provider package splits.

Secondary users:

- Platform teams evaluating path-repository or split-repository Composer workflows.
- Reviewers verifying that package extraction did not weaken Composer-first adoption.

## Package Boundary

Create a monorepo package under:

```text
packages/provider-bedrock/
```

The package name must be:

```text
purple-php/provider-bedrock
```

The provider package owns:

- `Purple\Provider\Bedrock\BedrockProvider`
- Bedrock request construction and response normalization.
- Bedrock region and endpoint behavior.
- Bedrock-specific tests and fixtures.
- A package-local factory or helper for creating a Bedrock-backed `Sdk` from core contracts.

The core SDK keeps:

- `Purple\Contracts\Provider\Provider`
- `Purple\Contracts\Provider\ProviderRequest`
- `Purple\Contracts\Provider\ProviderResponse`
- `Purple\Contracts\Provider\ProviderUsage`
- `Purple\ProviderProfile`
- `Purple\Sdk::fromProvider()`
- Policy, audit, schema, fake provider, runtime, sidecar protocol, native acceptance, and Composer baseline behavior.

The core SDK must not import `Purple\Provider\Bedrock\BedrockProvider` after extraction.

## Functional Requirements

| ID | Requirement | Status |
| --- | --- | --- |
| FR-001 | Add a `packages/provider-bedrock` Composer package named `purple-php/provider-bedrock` with its own autoload, dev autoload, tests, and Composer scripts. | Planned |
| FR-002 | Move the Bedrock provider implementation and provider-specific tests out of core into the provider package. | Planned |
| FR-003 | Keep provider contracts, `ProviderProfile::bedrock()`, and `Sdk::fromProvider()` in the core SDK so applications can still compose provider packages through stable core contracts. | Planned |
| FR-004 | Remove core SDK runtime imports or convenience factory code that requires the Bedrock provider package to be installed. | Planned |
| FR-005 | Add a package-local Bedrock SDK factory or documented construction helper so users installing `purple-php/provider-bedrock` can still create a Bedrock-backed `Sdk` ergonomically. | Planned |
| FR-006 | Prove core `composer check` passes without the Bedrock package being required by the root Composer install. | Planned |
| FR-007 | Prove the Bedrock package test suite passes without AWS credentials, live network calls, or AWS SDK dependencies by using injectable transport fixtures. | Planned |
| FR-008 | Document the package split, installation shape, and migration path from core `Sdk::bedrock()` to the optional provider package. | Planned |
| FR-009 | Update roadmap and enterprise docs so Bedrock is represented as the first optional provider package split, not as a required core dependency. | Planned |

## Non-Goals

This mission must not split Azure, OpenAI, sidecar provider, secret resolvers, audit exporters, domain workflow adapters, native runtime, or sidecar runtime packages.

This mission must not add AWS SDK dependencies.

This mission must not require AWS credentials or live AWS calls in default tests.

This mission must not publish Packagist packages or create a separate GitHub repository.

This mission must not redesign provider contracts or agent execution semantics.

This mission must not remove `ProviderProfile::bedrock()` from core unless a work package proves a replacement profile story that preserves policy/audit defaults.

## Acceptance Criteria

AC1: `packages/provider-bedrock/composer.json` declares `purple-php/provider-bedrock` and maps `Purple\Provider\Bedrock\` to package-local source files.

AC2: Root core source no longer contains the Bedrock provider implementation or imports it from `Sdk.php`.

AC3: A Bedrock package test proves request construction, endpoint/region behavior, response parsing, and usage normalization using an injectable transport.

AC4: A package-level factory or documented helper creates a Bedrock-backed `Purple\Sdk` through core `Sdk::fromProvider()`.

AC5: Root `composer check` passes with no Bedrock package dependency required by root `composer.json`.

AC6: Bedrock package validation passes from `packages/provider-bedrock` without AWS credentials, live network access, AWS SDK packages, sidecar services, or native extensions.

AC7: README, enterprise docs, and/or architecture docs explain that Bedrock is optional and describe the migration from core `Sdk::bedrock()` to the provider package.

AC8: The mission produces an issue matrix and mission review evidence showing the optional-provider split did not regress Composer-first validation.
