# Issue Matrix: Bedrock Provider Package Split

This mission does not close external tracker issues. Rows track mission-scoped package split issues resolved by the work packages.

| issue | verdict | evidence_ref | title | scope | wp | fr |
|---|---|---|---|---|---|---|
| BEDROCK-WP01 | fixed | `packages/provider-bedrock/composer.json`; `packages/provider-bedrock/phpunit.xml.dist`; `packages/provider-bedrock/phpstan.neon.dist`; `packages/provider-bedrock/.php-cs-fixer.dist.php`; `composer validate --working-dir=packages/provider-bedrock --strict`; acceptance matrix FR-001 | Provider package skeleton | provider-packaging | WP01 | FR-001 |
| BEDROCK-WP02 | fixed | `packages/provider-bedrock/src/BedrockProvider.php`; `packages/provider-bedrock/src/BedrockSdk.php`; `packages/provider-bedrock/tests/BedrockProviderTest.php`; `packages/provider-bedrock/tests/BedrockSdkTest.php`; `composer check --working-dir=packages/provider-bedrock`; acceptance matrix FR-002, FR-005, FR-007 | Bedrock provider extraction and factory | provider-packaging | WP02 | FR-002, FR-005, FR-007 |
| BEDROCK-WP03 | fixed | `src/Sdk.php`; `tests/SdkTest.php`; `src/ProviderProfile.php`; `composer validate --strict`; `composer check`; `rg -n "BedrockProvider|Provider\\Bedrock|Sdk::bedrock" src tests composer.json`; acceptance matrix FR-003, FR-004, FR-006 | Core decoupling and Composer baseline | core-baseline | WP03 | FR-003, FR-004, FR-006 |
| BEDROCK-VALIDATION | fixed | `composer check`; `composer check --working-dir=packages/provider-bedrock`; `acceptance-matrix.json`; acceptance matrix FR-006, FR-007 | Root and package validation evidence | validation | WP03, WP04 | FR-006, FR-007 |
| BEDROCK-DOCS | fixed | `README.md`; `docs/enterprise/README.md`; `docs/architecture/001-enterprise-adapter-split.md`; `outline.md`; `issue-matrix.md`; acceptance matrix FR-008, FR-009 | Optional provider documentation and roadmap evidence | documentation | WP04 | FR-008, FR-009 |
