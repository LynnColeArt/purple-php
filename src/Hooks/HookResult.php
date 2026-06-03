<?php

declare(strict_types=1);

namespace Purple\Hooks;

final readonly class HookResult
{
    /**
     * @param array<string, mixed> $modifications
     * @param list<string> $warnings
     */
    public function __construct(
        public HookAction $action,
        public ?string $message = null,
        public array $modifications = [],
        public array $warnings = [],
    ) {
    }

    public static function allow(): self
    {
        return new self(HookAction::Allow);
    }

    public static function block(string $message): self
    {
        return new self(HookAction::Block, $message);
    }

    public static function warn(string $message): self
    {
        return new self(HookAction::Warn, $message, warnings: [$message]);
    }

    /**
     * @param array<string, mixed> $modifications
     */
    public static function modify(array $modifications): self
    {
        return new self(HookAction::Modify, modifications: $modifications);
    }

    public static function requireApproval(string $message): self
    {
        return new self(HookAction::RequireApproval, $message);
    }

    public static function retry(string $message): self
    {
        return new self(HookAction::Retry, $message);
    }

    public static function fail(string $message): self
    {
        return new self(HookAction::Fail, $message);
    }
}
