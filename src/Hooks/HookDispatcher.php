<?php

declare(strict_types=1);

namespace Purple\Hooks;

final readonly class HookDispatcher
{
    /** @var list<RuntimeHook> */
    private array $hooks;

    /**
     * @param list<RuntimeHook> $hooks
     */
    public function __construct(array $hooks = [])
    {
        $this->hooks = $hooks;
    }

    public function dispatch(HookEvent $event): HookResult
    {
        $warnings = [];
        $modifications = [];

        foreach ($this->hooks as $hook) {
            $result = $hook->handle($event);
            $warnings = array_merge($warnings, $result->warnings);

            if ($result->action === HookAction::Warn) {
                continue;
            }

            if ($result->action === HookAction::Modify) {
                $modifications = array_merge($modifications, $result->modifications);
                continue;
            }

            if ($this->isTerminal($result)) {
                return new HookResult(
                    action: $result->action,
                    message: $result->message,
                    modifications: array_merge($modifications, $result->modifications),
                    warnings: $warnings,
                );
            }
        }

        if ($modifications !== []) {
            return new HookResult(HookAction::Modify, modifications: $modifications, warnings: $warnings);
        }

        return new HookResult(HookAction::Allow, warnings: $warnings);
    }

    private function isTerminal(HookResult $result): bool
    {
        return in_array($result->action, [
            HookAction::Block,
            HookAction::RequireApproval,
            HookAction::Retry,
            HookAction::Fail,
        ], true);
    }
}
