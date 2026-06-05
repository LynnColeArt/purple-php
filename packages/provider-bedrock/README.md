# Purple PHP Bedrock Provider

Optional AWS Bedrock provider adapter for Purple PHP.

This package is the opt-in home for Bedrock-specific provider behavior. The core `purple-php/sdk` package keeps the provider contracts, policy, audit, schema, and `Sdk::fromProvider()` composition surface. Installing the core SDK alone does not require this package, AWS credentials, AWS SDK dependencies, live network calls, sidecar services, or native extensions.

## Release Status

`purple-php/provider-bedrock` is published on Packagist from the standalone GitHub release repository at `https://github.com/LynnColeArt/purple-php-provider-bedrock`.

The first release line is `0.1.x`, beginning with `0.1.0`.

Package consumers should install the provider explicitly:

```bash
composer require purple-php/provider-bedrock:^0.1
```

Applications should construct a Bedrock-backed SDK through the package helper:

```php
use Purple\Provider\Bedrock\BedrockSdk;

$sdk = BedrockSdk::create(
    accessKey: $_ENV['AWS_ACCESS_KEY_ID'],
    secretKey: $_ENV['AWS_SECRET_ACCESS_KEY'],
    region: 'us-east-1',
    model: 'anthropic.claude-3-haiku-20240307-v1:0',
);
```

The root SDK no longer exposes `Sdk::bedrock()`. Core applications that do not install this package should compose providers through `Purple\Sdk::fromProvider()`.

## Local Validation

During local monorepo development this package resolves `purple-php/sdk` through a Composer path repository pointing at the repository root.

```bash
composer validate --working-dir=packages/provider-bedrock --strict
composer install --working-dir=packages/provider-bedrock
composer check --working-dir=packages/provider-bedrock
```

Default package tests use injectable transports and fixtures. They must not require AWS credentials, live Bedrock calls, AWS SDK dependencies, sidecar services, or native extensions.

Real AWS signing, credential discovery, and live Bedrock integration tests are future opt-in work. They must stay outside the default Composer validation path unless a later release mission explicitly designs that surface.
