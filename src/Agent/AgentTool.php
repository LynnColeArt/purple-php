<?php

declare(strict_types=1);

namespace Purple\Agent;

use Purple\Tool\ToolDefinition;

final readonly class AgentTool
{
    /** @var callable(array<string, mixed>): mixed */
    private mixed $callback;

    /**
     * @param callable(array<string, mixed>): mixed $callback
     */
    public function __construct(
        public ToolDefinition $definition,
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    public function name(): string
    {
        return $this->definition->name;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function invoke(array $input): mixed
    {
        return ($this->callback)($input);
    }
}
