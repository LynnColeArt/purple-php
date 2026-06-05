# Implementation Plan: Bedrock Provider Package Split

**Branch**: `kitty/mission-bedrock-provider-package-split-01KTAHKT`  
**Date**: 2026-06-05  
**Spec**: `kitty-specs/bedrock-provider-package-split-01KTAHKT/spec.md`

## Summary

Extract the AWS Bedrock adapter from the core SDK into a monorepo Composer package at `packages/provider-bedrock`, named `purple-php/provider-bedrock`. The core SDK keeps stable provider contracts, `ProviderProfile::bedrock()`, and `Sdk::fromProvider()`, but no longer imports or constructs `BedrockProvider` directly. The provider package owns `BedrockProvider`, package-local tests, and an ergonomic factory/helper that composes a Bedrock-backed `Purple\Sdk` through the core contract surface.

The implementation should prove two independent validation paths:

- Root core validation runs without the Bedrock package installed as a root dependency.
- Bedrock package validation runs from `packages/provider-bedrock` through path-repository access to the local core SDK and uses only fake/injectable transports.

## Technical Context

**Language/Version**: PHP 8.2+  
**Primary Dependencies**: Composer, PHPUnit 11.5, PHPStan 2.1, php-cs-fixer 3.75; no AWS SDK dependency in this mission  
**Storage**: N/A; package extraction affects source layout and Composer metadata only  
**Testing**: root `composer check`; package-local PHPUnit/PHPStan/php-cs-fixer scripts under `packages/provider-bedrock`; package tests must use injectable transports  
**Target Platform**: PHP library packages installed through Composer on Linux/macOS/CI environments  
**Project Type**: Composer monorepo with root SDK package plus optional provider package under `packages/`  
**Performance Goals**: No new runtime hot path beyond current Bedrock request/response transformation; extraction should not add network, filesystem, or autoload overhead to core validation  
**Constraints**: root Composer install must not require AWS credentials, AWS SDK packages, sidecar services, native extensions, or live network calls  
**Scale/Scope**: one provider package split; future provider splits should be able to copy the pattern

## Charter Check

The mission preserves the project identity and boundaries already recorded in the roadmap:

- Composer-first remains the core adoption path.
- Optional provider packages may add enterprise-specific behavior without growing the core install surface.
- Package extraction must be test-first and reversible through core contracts.
- Runtime/native/sidecar package work stays out of scope.

No charter exception is expected.

## Project Structure

### Documentation

```text
kitty-specs/bedrock-provider-package-split-01KTAHKT/
├── spec.md
├── plan.md
├── tasks.md
├── wps.yaml
├── issue-matrix.md
└── acceptance-matrix.json
```

### Source Code

```text
composer.json
README.md
docs/
├── architecture/001-enterprise-adapter-split.md
└── enterprise/README.md
src/
├── Contracts/Provider/
├── ProviderProfile.php
└── Sdk.php
tests/
├── ProviderProfileTest.php
└── SdkTest.php
packages/
└── provider-bedrock/
    ├── composer.json
    ├── README.md
    ├── phpstan.neon.dist
    ├── phpunit.xml.dist
    ├── .php-cs-fixer.dist.php
    ├── src/
    │   └── BedrockProvider.php
    └── tests/
        ├── BedrockProviderTest.php
        └── BedrockSdkFactoryTest.php
```

**Structure Decision**: Use a monorepo package under `packages/provider-bedrock` for this mission. Do not create a separate repository or publish packages. The package should be valid as a standalone Composer package while local validation uses a path repository pointing back to the root SDK.

## Implementation Strategy

1. Create the package skeleton.

   - Add `packages/provider-bedrock/composer.json` with package name `purple-php/provider-bedrock`.
   - Require `php: ^8.2` and `purple-php/sdk`.
   - Add package-local PSR-4 autoload for `Purple\Provider\Bedrock\`.
   - Add package-local dev autoload for package tests.
   - Add local `test`, `analyse`, `lint`, and `check` scripts.

2. Move Bedrock implementation into the provider package.

   - Move `src/Provider/Bedrock/BedrockProvider.php` to `packages/provider-bedrock/src/BedrockProvider.php` while preserving namespace `Purple\Provider\Bedrock`.
   - Move provider-specific tests to `packages/provider-bedrock/tests`.
   - Keep injectable transport semantics and no-live-network default behavior.

3. Decouple root core SDK from Bedrock implementation.

   - Remove `use Purple\Provider\Bedrock\BedrockProvider` from `src/Sdk.php`.
   - Remove or replace `Sdk::bedrock()` so core no longer instantiates the optional provider class.
   - Keep `ProviderProfile::bedrock()` in core as a profile/value object for policy and audit defaults.
   - Keep `Sdk::fromProvider()` as the core composition point.

4. Add package-local ergonomics.

   - Add a Bedrock package factory/helper, for example `Purple\Provider\Bedrock\BedrockSdk`, that accepts `ProviderProfile`, optional audit/policy/schema collaborators, injectable transport, and region.
   - The factory should call `Sdk::fromProvider(new BedrockProvider(...), $profile, ...)`.
   - Tests should prove this replacement covers the old core convenience path.

5. Update docs and migration guidance.

   - README and enterprise docs should say Bedrock is optional and lives in `purple-php/provider-bedrock`.
   - Architecture ADR should record that extraction has moved from planned/deferred to implemented by this mission.
   - Migration text should replace `Sdk::bedrock(...)` with package factory or explicit `Sdk::fromProvider(...)`.

6. Add acceptance and issue evidence.

   - Record an acceptance matrix mapping FRs to commands and files.
   - Record an issue matrix with fixed rows for package skeleton, extraction, core decoupling, package validation, docs, and Composer baseline.

## Validation Strategy

Core validation:

```bash
composer install
composer check
php examples/runtime/durable-sidecar-handoff.php
php examples/runtime/durable-sidecar-resume.php
```

Package validation:

```bash
composer install --working-dir=packages/provider-bedrock
composer check --working-dir=packages/provider-bedrock
```

Focused validation:

```bash
vendor/bin/phpunit -c phpunit.xml.dist tests/SdkTest.php tests/ProviderProfileTest.php
packages/provider-bedrock/vendor/bin/phpunit -c packages/provider-bedrock/phpunit.xml.dist
```

Search checks:

```bash
rg -n "BedrockProvider|Sdk::bedrock|Provider\\Bedrock" src tests README.md docs composer.json packages
find . -maxdepth 3 -name composer.json -not -path './vendor/*' -print
```

The search checks should show Bedrock implementation references only in the optional package and docs/tests that intentionally mention the migration path.

## Risks And Mitigations

| Risk | Mitigation |
| --- | --- |
| Composer path dependency between root and provider package becomes circular or brittle. | Keep root free of `purple-php/provider-bedrock` dependency; validate the provider package from its own working directory with a path repository back to the root SDK. |
| Removing `Sdk::bedrock()` breaks current users without guidance. | Add a package-local factory and migration docs that preserve an ergonomic construction path. |
| Package tests accidentally depend on root dev autoload or root vendor state. | Run package validation from `packages/provider-bedrock` with package-local config and autoload. |
| Bedrock extraction leaks AWS assumptions into core policy/audit contracts. | Keep only generic provider/profile contracts in root; package owns Bedrock-specific endpoint, region, signing, and response behavior. |
| Future provider split pattern becomes overfit to Bedrock. | Document which package skeleton pieces are provider-generic and which are Bedrock-specific. |

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Additional Composer package inside the repo | The mission is specifically about proving optional provider packaging. | Keeping Bedrock in core would preserve the current coupling and fail FR-001 through FR-004. |
