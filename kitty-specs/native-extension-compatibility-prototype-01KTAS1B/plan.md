# Implementation Plan: Native Extension Compatibility Prototype

## Summary

Add an optional native extension compatibility prototype around the existing `NativeRuntime` contract. The implementation stays in the core SDK because it is a contract harness, not a native package or compiled extension. It will provide a reusable compatibility checker, a CLI-accessible local runner, docs, and mission evidence.

## Architecture And Boundaries

The prototype belongs to the existing runtime-native surface:

- `src/Runtime` owns `PhpExtensionBridge`, native results, metrics, readiness metadata, and the new compatibility harness.
- `src/Cli/PurpleCli.php` owns the local prototype command surface.
- `tests/Runtime` and `tests/Cli` own Composer-safe behavior coverage.
- `docs/enterprise/native-runtime-acceptance.md`, `docs/enterprise/README.md`, `README.md`, `outline.md`, and architecture docs own adoption guidance.

The compatibility harness should accept any `NativeRuntime` implementation. Fixture mode may construct `PhpExtensionBridge` with an injected PHP callable; extension mode may construct `PhpExtensionBridge` with a named extension. Neither mode should require a compiled extension for default validation.

## Data Flow

1. A caller supplies a `NativeRuntime` implementation to the compatibility harness.
2. The harness invokes `runtime.acceptance.ping` with a deterministic tenant payload.
3. The harness validates the result shape:
   - operation matches the acceptance operation;
   - status is `ok`;
   - payload answer is `native-compatible`;
   - metrics are present and non-negative.
4. The harness returns a structured report:
   - `compatible` for valid runtimes;
   - `incompatible` for malformed or contract-breaking responses;
   - `unavailable` for missing extension/runtime paths.
5. The CLI writes the report as JSON and exits `0` only for compatible reports.

Bridge-level exceptions should continue to exist for direct SDK callers. The prototype runner should catch them and normalize the compatibility result so CLI users receive a stable report.

## Work Packages

WP01 adds the reusable native compatibility harness and runtime tests.

WP02 adds the CLI-accessible compatibility runner and CLI tests.

WP03 updates docs, roadmap status, and mission evidence.

## Verification

Required validation:

- `vendor/bin/phpunit -c phpunit.xml.dist tests/Runtime/NativeRuntimeCompatibilityTest.php tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php`
- `vendor/bin/phpunit -c phpunit.xml.dist tests/Cli/PurpleCliTest.php`
- `composer check`
- `composer check --working-dir=packages/provider-bedrock`
- `bin/purple native check fixture`
- `bin/purple native check extension definitely_missing_purple_native_extension`
- `php -r "json_decode(file_get_contents('kitty-specs/native-extension-compatibility-prototype-01KTAS1B/acceptance-matrix.json'), true, flags: JSON_THROW_ON_ERROR);"`
- `git diff --check`

## Risks

| Risk | Mitigation |
| --- | --- |
| Prototype language implies a compiled extension exists. | Use "compatibility prototype" and "fixture mode" language; document that extension mode is opt-in and may report unavailable. |
| CLI check hides real contract failures. | Return explicit `incompatible` or `unavailable` reports with messages and non-zero exit codes. |
| Native runtime work blurs into sidecar or provider packaging. | Keep docs explicit: this is native compatibility contract work, not Bedrock release, provider split, or sidecar daemon work. |
| Default validation accidentally requires native dependencies. | Test fixture and unavailable-extension paths in Composer mode; avoid new Composer requirements. |

## Rollout

This mission lands as normal SDK changes. No native binary is built, no service is deployed, and no package is published. Users can try the prototype locally through `bin/purple native check fixture`, and platform teams can later run `bin/purple native check extension [extension-name]` after deliberately installing a compatible extension.
