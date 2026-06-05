# Issue Matrix: Native Extension Compatibility Prototype

| issue | verdict | evidence_ref | title | scope | wp | fr |
| --- | --- | --- | --- | --- | --- | --- |
| NCP-001 | verified-already-fixed | `tests/Runtime/NativeRuntimeCompatibilityTest.php` | Compatibility harness returns structured verdicts | runtime harness | WP01 | FR-001, FR-002, FR-003 |
| NCP-002 | verified-already-fixed | `tests/Runtime/NativeAcceptance/NativeRuntimeAcceptanceTest.php` | Product fixture satisfies reusable native acceptance assertions | native acceptance | WP01 | FR-002, FR-005 |
| NCP-003 | verified-already-fixed | `tests/Cli/PurpleCliTest.php` | CLI fixture and extension modes report compatible/unavailable outcomes | CLI | WP02 | FR-004, FR-005 |
| NCP-004 | verified-already-fixed | `docs/enterprise/native-runtime-acceptance.md` | Native docs explain command shape and optional extension semantics | docs | WP03 | FR-006 |
| NCP-005 | verified-already-fixed | `outline.md` | Roadmap marks Phase 5.5 complete and updates follow-up list | roadmap | WP03 | FR-007 |
| NCP-006 | verified-already-fixed | `composer check`; `composer check --working-dir=packages/provider-bedrock` | Root and provider validation remain Composer-safe | validation | WP03 | FR-008 |
