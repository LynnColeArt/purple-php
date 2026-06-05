# Purple PHP Bedrock Provider

Optional AWS Bedrock provider adapter for Purple PHP.

This package is the opt-in home for Bedrock-specific provider behavior. The core `purple-php/sdk` package keeps the provider contracts, policy, audit, schema, and `Sdk::fromProvider()` composition surface. Installing the core SDK alone should not require this package, AWS credentials, AWS SDK dependencies, live network calls, sidecar services, or native extensions.

During local monorepo development this package resolves `purple-php/sdk` through a Composer path repository pointing at the repository root.

```bash
composer install --working-dir=packages/provider-bedrock
composer check --working-dir=packages/provider-bedrock
```

Default package tests must use injectable transports and fixtures. Real AWS signing, credential discovery, and live Bedrock calls are future opt-in work, not part of this package skeleton.
