---
affected_files: []
cycle_number: 1
mission_slug: 001-purple-php-enterprise-ai-sdk
reproduction_command:
reviewed_at: '2026-06-03T15:22:39Z'
reviewer_agent: unknown
verdict: rejected
wp_id: WP04
---

# WP04 Review Feedback

Verdict: reject for one blocking enforcement-order issue.

## Blocking Finding

1. `before_tool_call` hook modifications happen after the tool policy decision.

Evidence: On `kitty/001-purple-php-enterprise-ai-sdk-WP04` at `1e1fea8`, `AgentRunner::executeToolInstruction()` runs `decidePolicy('agent.tool_call', ...)` at `src/Agent/AgentRunner.php:242`, records the policy result, and only then dispatches the `before_tool_call` hook at `src/Agent/AgentRunner.php:269`. If the hook returns `HookResult::modify(['input' => ...])`, the runner applies that modified input at `src/Agent/AgentRunner.php:280` and then invokes the tool at `src/Agent/AgentRunner.php:298`.

Why this blocks acceptance: WP04 requires policy enforcement before every tool call and tests that hooks cannot bypass policy. With the current order, an input-aware policy can approve the original provider-supplied input, then a hook can replace it with a different input before the side effect. The side-effect level is still checked, but the final tool invocation payload is not the payload that policy saw.

## Required Remediation

- Dispatch `before_tool_call` early enough to collect hook modifications.
- Apply any hook-modified tool input.
- Run the mandatory `agent.tool_call` policy decision after the final tool input is known and include that final input in policy metadata.
- Keep approval checks and tool invocation after the final policy decision.
- Add a regression test with an input-aware policy proving a hook-modified forbidden input is denied before the tool callback runs.

No `contracts/` artifact exists for this mission, so the contract round-trip review was skipped.
