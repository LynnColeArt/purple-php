# Bedrock Provider Release Readiness

`purple-php/provider-bedrock` is the optional AWS Bedrock provider package for Purple PHP. It is prepared for a future public release from this monorepo, but it is not published to Packagist yet.

## Release Contract

| Field | Value |
| --- | --- |
| Package | `purple-php/provider-bedrock` |
| First intended version | `0.1.0` |
| Version line | `0.1.x` |
| Core dependency | `purple-php/sdk` `0.1.x-dev` during monorepo development |
| Runtime baseline | Composer-first, optional provider package |
| Default validation | No AWS credentials, live Bedrock calls, AWS SDK dependency, sidecar service, or native extension |

After publication, the intended install command is:

```bash
composer require purple-php/provider-bedrock:^0.1
```

Until publication, local validation uses the package path repository:

```bash
composer install --working-dir=packages/provider-bedrock
composer check --working-dir=packages/provider-bedrock
```

## Release Sequence

1. Confirm `main` is clean and synced with the protected remote branch.
2. Run root validation:

   ```bash
   composer validate --strict
   composer check
   ```

3. Run package validation:

   ```bash
   composer validate --working-dir=packages/provider-bedrock --strict
   composer check --working-dir=packages/provider-bedrock
   ```

4. Confirm `packages/provider-bedrock/CHANGELOG.md` has the final `0.1.0` release date.
5. Confirm `packages/provider-bedrock/composer.json` metadata matches the published package name and dependency line.
6. Create a signed release tag only after validation evidence is recorded.
7. Publish to Packagist only after package ownership and repository URL are confirmed.
8. Verify Packagist resolves `purple-php/provider-bedrock` and that a clean consumer project can install it with `composer require purple-php/provider-bedrock:^0.1`.

## Packagist Checklist

- Package name is `purple-php/provider-bedrock`.
- License is MIT.
- PHP requirement remains `^8.2`.
- Root SDK dependency is compatible with the first public SDK release line.
- Package autoload maps `Purple\Provider\Bedrock\` to `src/`.
- Package tests run without AWS credentials or live services.
- No publish token, Packagist credential, or deployment secret is committed to this repository.
- Release notes include migration guidance from removed root `Sdk::bedrock()` usage to `Purple\Provider\Bedrock\BedrockSdk::create()`.
- Root `purple-php/sdk` remains installable and testable without requiring the Bedrock package.

## Rollback Notes

If publication is attempted later and the package metadata is wrong, do not re-couple Bedrock into the root SDK. Fix the provider package metadata, tag a corrected release, and document the correction in the package changelog.

If a live AWS integration issue is found, keep it outside the default validation path. Live integration tests require a separate opt-in mission with explicit credential and network boundaries.
