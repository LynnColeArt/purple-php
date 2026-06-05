# Provider Bedrock Release Readiness

Source roadmap: `outline.md` Phase 5.2 follow-up candidate 1 and `docs/architecture/001-enterprise-adapter-split.md`.

## Mission Goal

Prepare the optional `purple-php/provider-bedrock` package for a future public release without publishing it yet.

The mission must turn the completed monorepo package split into a release-ready package surface: clear install and migration docs, explicit versioning policy, Packagist publication checklist, CI coverage for the root SDK and provider package, and package-local release notes. The core `purple-php/sdk` package must remain Composer-first and must not require Bedrock, AWS credentials, AWS SDK dependencies, live network calls, sidecar services, or native extensions.

## Product Context

The Bedrock provider package split is complete. The package now exists at `packages/provider-bedrock` and validates independently. It is not yet published to Packagist, not tagged as a release line, and not covered by repository CI.

Release readiness should make the next external step boring:

- Maintainers know what version to tag first.
- Users know how to install once the package is published.
- Reviewers know which Composer commands validate root and provider package compatibility.
- CI verifies the same Composer-first guardrails that protected the package split.
- Release notes describe the migration from removed root `Sdk::bedrock()` usage to package-local `BedrockSdk::create()`.

## Target Users

Primary users:

- Maintainers preparing the first `purple-php/provider-bedrock` package release.
- PHP teams evaluating whether Bedrock remains opt-in.
- Reviewers checking that package release work did not couple Bedrock back into the core SDK.

Secondary users:

- Platform teams planning additional provider package releases.
- Future automation that will publish tags or Packagist packages after human approval.

## Functional Requirements

| ID | Requirement | Status |
| --- | --- | --- |
| FR-001 | Document the provider package release contract: package name, first version line, dependency on `purple-php/sdk`, install shape, and non-published current status. | Planned |
| FR-002 | Add package-local release notes for the first Bedrock provider package release, including migration guidance from root `Sdk::bedrock()` to `Purple\Provider\Bedrock\BedrockSdk::create()`. | Planned |
| FR-003 | Add CI that validates the root SDK and Bedrock provider package separately through Composer commands. | Planned |
| FR-004 | CI must preserve the Composer-first baseline: no AWS credentials, live AWS calls, AWS SDK dependency, native extension, or sidecar service may be required for default validation. | Planned |
| FR-005 | Document a Packagist publication checklist and release sequence that keeps repository tagging, package metadata, and validation evidence aligned. | Planned |
| FR-006 | Update roadmap and architecture docs so release readiness is represented as a packaging-track step after Phase 5.2, not as another provider split. | Planned |
| FR-007 | Capture mission evidence proving root and provider package validation pass after release-readiness changes. | Planned |

## Non-Goals

This mission must not publish a Packagist package.

This mission must not create a separate GitHub repository.

This mission must not add AWS SDK dependencies.

This mission must not add live Bedrock integration tests.

This mission must not split Azure, OpenAI, sidecar provider, secrets, audit exporters, domain workflow adapters, native runtime, or sidecar runtime packages.

This mission must not reintroduce root `Sdk::bedrock()` or a root dependency on `purple-php/provider-bedrock`.

## Acceptance Criteria

AC1: The Bedrock package README and/or release docs state the package name, first intended version line, install shape, current unpublished status, and default validation commands.

AC2: `packages/provider-bedrock/CHANGELOG.md` contains first-release notes for `0.1.0` with migration guidance from removed root `Sdk::bedrock()` usage.

AC3: Repository CI validates root `composer check` and provider package `composer check --working-dir=packages/provider-bedrock` as separate jobs or clearly separate steps.

AC4: CI and docs state that default validation requires no AWS credentials, live network calls, AWS SDK dependency, sidecar process, or native extension.

AC5: Release docs include a Packagist publication checklist and release sequence that can be followed later without changing package boundaries.

AC6: Roadmap and architecture docs mark the release-readiness step as complete after implementation while keeping the next provider split as a later optional candidate.

AC7: The mission records issue and acceptance evidence for docs, CI, and Composer validation.
