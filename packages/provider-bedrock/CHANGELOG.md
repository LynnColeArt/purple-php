# Changelog

All notable changes to `purple-php/provider-bedrock` are recorded here.

## 0.1.0 - 2026-06-05

First public GitHub release for the optional AWS Bedrock provider package.

### Added

- Optional Composer package metadata for `purple-php/provider-bedrock`.
- Package-local `Purple\Provider\Bedrock\BedrockProvider`.
- Package-local `Purple\Provider\Bedrock\BedrockSdk::create()` helper for constructing a Bedrock-backed `Purple\Sdk`.
- Package-local PHPUnit, PHPStan, and php-cs-fixer validation.
- Injectable transport fixtures for default tests without AWS credentials or live network calls.

### Changed

- Bedrock provider code now lives outside the root `purple-php/sdk` package.
- Bedrock users should install this package explicitly once it is published.
- Bedrock SDK construction moves from removed root `Sdk::bedrock()` usage to `Purple\Provider\Bedrock\BedrockSdk::create()`.

### Migration

Before the provider split, Bedrock code could use a root factory:

```php
$sdk = Sdk::bedrock(/* credentials */);
```

After the split, install the provider package and use the package-local helper:

```php
use Purple\Provider\Bedrock\BedrockSdk;

$sdk = BedrockSdk::create(
    accessKey: $accessKey,
    secretKey: $secretKey,
    region: $region,
    model: $model,
);
```

Applications with custom provider wiring may also instantiate `BedrockProvider` directly and pass it to `Purple\Sdk::fromProvider()`.

### Validation

Default release validation for this package is:

```bash
composer validate --working-dir=packages/provider-bedrock --strict
composer check --working-dir=packages/provider-bedrock
```

This validation path must remain free of AWS credentials, live Bedrock calls, AWS SDK dependencies, sidecar services, and native extensions.
