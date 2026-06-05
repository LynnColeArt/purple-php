# Native Extension Compatibility Prototype

Source roadmap: `outline.md` candidate follow-up mission 2 and `docs/enterprise/native-runtime-acceptance.md`.

## Mission Goal

Prototype an optional native extension compatibility runner that exercises Purple PHP's existing native runtime acceptance boundary without requiring a compiled extension for normal SDK installation or validation.

The mission must turn the current PHP-level native bridge and acceptance fixture into a product-facing compatibility surface: a reusable runtime compatibility check, a CLI-accessible local prototype path, docs that explain how platform teams can run the same contract against a real extension later, and evidence that Composer mode remains the stable baseline.

## Product Context

Phase 5 and Phase 5.1 made native runtime support optional and concrete through `Purple\Contracts\Runtime\NativeRuntime`, `PhpExtensionBridge`, `NativeRuntimeResult`, `RuntimeMetrics`, and reusable acceptance tests. That proved the contract inside PHPUnit, but platform teams still need a small compatibility harness they can run deliberately when evaluating a native extension.

This mission should provide that next bridge without starting real native implementation work:

- Run the same acceptance operation shape used by `tests/Runtime/NativeAcceptance`.
- Report a structured compatibility verdict and native metrics.
- Provide a Composer-safe fixture mode that proves the path works without a compiled extension.
- Provide an extension mode that fails closed when `purple_native` or `purple_native_invoke` is unavailable.
- Keep the default root SDK and provider package validation free of native extension requirements.

## Target Users

Primary users:

- Platform teams evaluating a future `purple_native` PHP extension or compatible native invoker.
- SDK maintainers proving the native runtime contract is executable outside PHPUnit.
- Reviewers checking that native work remains optional and does not burden Composer-first adoption.

Secondary users:

- Future implementers of a compiled Zig/C native runtime.
- Enterprise deployment teams comparing Composer, sidecar, and native runtime modes.

## Functional Requirements

| ID | Requirement | Status |
| --- | --- | --- |
| FR-001 | Add a reusable native extension compatibility harness that can run the native acceptance ping operation against any `NativeRuntime` implementation and return a structured verdict. | Planned |
| FR-002 | The harness must preserve the acceptance contract: non-empty operation, `ok` status, `native-compatible` answer payload, and non-negative runtime metrics. | Planned |
| FR-003 | The harness must report unavailable or incompatible native runtimes without throwing through the CLI surface, while still allowing lower-level bridge exceptions to exist for direct SDK callers. | Planned |
| FR-004 | Add a CLI-accessible prototype path under `bin/purple` that can run a Composer-safe fixture check and an extension availability check without STDIN interactivity, a sidecar service, cloud SDK, AWS credentials, or live network access. | Planned |
| FR-005 | Add tests covering compatible fixture runs, incompatible payloads, unavailable extensions, CLI fixture output, CLI missing-extension output, and CLI usage errors. | Planned |
| FR-006 | Update native runtime docs and enterprise docs so maintainers can see the compatibility command, output shape, fixture mode, extension mode, and optional-native guardrails. | Planned |
| FR-007 | Update roadmap and architecture docs so the native compatibility prototype is represented as a completed runtime-track step, separate from Bedrock package publication, future provider splits, and sidecar daemon/HTTP transport work. | Planned |
| FR-008 | Capture mission evidence proving Composer-mode root validation and provider-package validation still pass without a required native extension. | Planned |

## Non-Goals

This mission must not implement a compiled PHP extension, Zig runtime, C ABI, FFI binding, PECL package, or native package publication.

This mission must not make `composer install`, `composer check`, provider package validation, or normal CLI diagnostics require `purple_native`, `purple_native_invoke`, a sidecar process, cloud SDK packages, AWS credentials, Vault credentials, or live network services.

This mission must not redesign `NativeRuntime`, `PhpExtensionBridge`, `NativeRuntimeResult`, or `RuntimeMetrics` beyond what is necessary to expose the compatibility prototype.

This mission must not publish Packagist packages or alter the Bedrock provider release track.

This mission must not start production daemon, HTTP transport, queue, distributed scheduler, or sidecar runtime work.

## Acceptance Criteria

AC1: A reusable compatibility harness can run the native acceptance ping operation against a provided `NativeRuntime` implementation and return a structured compatible verdict with metrics.

AC2: The harness returns an incompatible verdict for malformed native responses and an unavailable verdict for missing native extension paths without making the CLI crash with an uncaught exception.

AC3: `bin/purple` exposes a native compatibility prototype command that can run a fixture check successfully and an extension check that reports unavailable when no extension is installed.

AC4: Tests cover runtime harness behavior and CLI output for fixture, unavailable extension, and usage paths.

AC5: Docs explain how the fixture mode relates to the native acceptance boundary and how a future compiled extension can run the same compatibility check deliberately.

AC6: Roadmap docs mark the native extension compatibility prototype as complete after implementation while leaving Bedrock publication, future provider splits, and sidecar daemon/HTTP transport work as separate candidates.

AC7: The mission records issue and acceptance evidence for runtime behavior, CLI behavior, docs, roadmap updates, and Composer-safe validation.
