# Issue Matrix: Native Extension Compatibility Prototype

| issue | verdict | evidence_ref | title | scope | wp | fr |
| --- | --- | --- | --- | --- | --- | --- |
| NCP-001 | pass | `tests/Runtime/NativeRuntimeCompatibilityTest.php` | Compatibility harness returns structured verdicts | runtime harness | WP01 | FR-001, FR-002, FR-003 |
| NCP-002 | pass | `tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php` | Product fixture satisfies reusable native acceptance assertions | native acceptance | WP01 | FR-002, FR-005 |
| NCP-003 | pass | `tests/Cli/PurpleCliTest.php` | CLI fixture and extension modes report compatible/unavailable outcomes | CLI | WP02 | FR-004, FR-005 |
| NCP-004 | pass | `docs/enterprise/native-runtime-acceptance.md` | Native docs explain command shape and optional extension semantics | docs | WP03 | FR-006 |
| NCP-005 | pass | `outline.md` | Roadmap marks Phase 5.5 complete and updates follow-up list | roadmap | WP03 | FR-007 |
| NCP-006 | pass | `composer check`; `composer check --working-dir=packages/provider-bedrock` | Root and provider validation remain Composer-safe | validation | WP03 | FR-008 |
