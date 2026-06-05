# Implementation Plan: Provider Bedrock Release Readiness

## Summary

Make `purple-php/provider-bedrock` ready for a later public release by adding release-facing package docs, first-release notes, CI coverage, and mission evidence. This mission is packaging-track work only. It does not publish a package or alter provider runtime behavior.

## Architecture And Boundaries

The release-readiness work sits around the already-split provider package:

- `packages/provider-bedrock` owns package-local install, validation, and release notes.
- `docs/release/provider-bedrock.md` owns the publication checklist and release sequence.
- `.github/workflows/ci.yml` owns repository validation for both root SDK and provider package.
- `outline.md`, `README.md`, `docs/architecture/001-enterprise-adapter-split.md`, and `docs/enterprise/README.md` own roadmap alignment.

The core SDK must stay provider-neutral. CI may install the provider package in its own job, but root validation must continue to run without requiring the provider package as a root dependency.

## Data Flow

Release readiness is documentation and automation flow:

1. Maintainer reads release docs and package changelog.
2. Maintainer validates root and provider package locally.
3. CI runs the same root and package checks independently.
4. Later, a human can tag and publish after confirming Packagist ownership and release metadata.

No runtime data flow changes are planned.

## Work Packages

WP01 documents the package release contract and first-release notes.

WP02 adds CI for root SDK and provider package validation.

WP03 updates roadmap/architecture evidence and records mission acceptance evidence.

## Verification

Required validation:

- `composer validate --strict`
- `composer check`
- `composer validate --working-dir=packages/provider-bedrock --strict`
- `composer check --working-dir=packages/provider-bedrock`
- `git diff --check`

CI validation should mirror the root and package `composer check` commands.

## Risks

| Risk | Mitigation |
| --- | --- |
| Release docs imply the package is already published. | State current unpublished status and separate release readiness from publication. |
| CI accidentally couples the root SDK to the provider package. | Keep root and provider jobs separate; root job runs only from root Composer metadata. |
| Versioning language overpromises compatibility. | Use first intended `0.1.0` line and keep SemVer promises modest until public release. |
| Publication checklist grows into release automation. | Keep this mission to docs and CI; no publish token, tag push, or Packagist API call. |

## Rollout

This mission lands as normal repository changes. No deployment, external service, or package publication occurs.
