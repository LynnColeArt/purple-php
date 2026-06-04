# Native Runtime Acceptance Boundary

Purple PHP keeps native runtime support optional. Composer mode must work without a compiled PHP extension, sidecar process, or native runtime binary.

The first native acceptance boundary is therefore a PHP-level contract around `Purple\Contracts\Runtime\NativeRuntime`.

## Compatible Runtime Behavior

A compatible native runtime must:

- accept a non-empty operation string;
- accept a JSON-compatible associative payload;
- return an associative response with a non-empty `status`;
- echo or provide a non-empty `operation`;
- return an associative `payload`;
- return `RuntimeMetrics`-compatible metrics when metrics are provided;
- fail closed with a clear runtime exception for malformed responses.

The normalized result is `Purple\Runtime\NativeRuntimeResult`:

```php
[
    'operation' => 'runtime.acceptance.ping',
    'status' => 'ok',
    'payload' => [
        'answer' => 'native-compatible',
    ],
    'metrics' => [
        'duration_ms' => 1.0,
        'memory_delta_bytes' => 0,
    ],
]
```

## Reusable Fixture

`tests/Runtime/NativeAcceptance/NativeRuntimeContractAssertions.php` contains the reusable acceptance fixture. A future compiled extension test should expose a compatible invoker or `NativeRuntime` implementation and run the same assertions.

The current Composer-mode implementation uses `PhpExtensionBridge` with an injected invoker. This proves the contract without loading a native extension.

## Required Failure Semantics

The bridge must reject:

- blank operation names;
- list-shaped top-level responses;
- list-shaped payload responses;
- blank or non-string statuses;
- blank or non-string operation names in responses;
- negative metric durations;
- missing extension/runtime availability when no invoker or compatible function exists.

## Composer Baseline

The native acceptance tests must run under `composer check` with no native extension installed. Native runtime adoption is a deployment choice, not an SDK installation requirement.
