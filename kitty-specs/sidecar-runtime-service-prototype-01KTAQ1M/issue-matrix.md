# Issue Matrix: Sidecar Runtime Service Prototype

This mission does not close external tracker issues. Rows track mission-scoped sidecar runtime prototype issues resolved by the work packages.

| issue | verdict | evidence_ref | title | scope | wp | fr |
|---|---|---|---|---|---|---|
| SIDECAR-SERVICE | fixed | `src/Runtime/Sidecar/SidecarRuntimeService.php`; `tests/Runtime/Sidecar/SidecarRuntimeServiceTest.php`; acceptance matrix FR-001, FR-002, FR-004 | Local sidecar runtime service handler for durable resume envelopes | runtime | WP01 | FR-001, FR-002, FR-004 |
| SIDECAR-CLI | fixed | `src/Cli/PurpleCli.php`; `tests/Cli/PurpleCliTest.php`; acceptance matrix FR-003, FR-004 | CLI prototype command for local durable resume service boundary | cli | WP02 | FR-003, FR-004 |
| SIDECAR-DOCS | fixed | `README.md`; `docs/enterprise/README.md`; `docs/enterprise/sidecar-durable-resume.md`; acceptance matrix FR-005 | Sidecar prototype docs and local-store command guidance | documentation | WP03 | FR-005 |
| ROADMAP-ALIGNMENT | fixed | `outline.md`; `README.md`; `docs/architecture/001-enterprise-adapter-split.md`; acceptance matrix FR-006 | Phase 5.4 roadmap and architecture alignment | documentation | WP03 | FR-006 |
| VALIDATION-EVIDENCE | fixed | `composer check`; `composer check --working-dir=packages/provider-bedrock`; `php examples/runtime/durable-sidecar-resume.php`; acceptance matrix JSON parse; acceptance matrix FR-007 | Mission acceptance and Composer baseline validation evidence | validation | WP03 | FR-007 |

## Notes

This mission implements a local sidecar runtime service prototype only. It does not create a production daemon, HTTP listener, process supervisor, sidecar package split, native extension, cloud SDK dependency, AWS credential requirement, or live network dependency.
