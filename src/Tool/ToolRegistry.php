<?php

declare(strict_types=1);

namespace Purple\Tool;

final class ToolRegistry
{
    /** @var array<string, ToolDefinition> */
    private array $tools = [];

    /**
     * @param list<ToolDefinition> $tools
     */
    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(ToolDefinition $tool): void
    {
        if (isset($this->tools[$tool->name])) {
            throw new ToolException(sprintf('Tool "%s" is already registered.', $tool->name));
        }

        $this->tools[$tool->name] = $tool;
    }

    public function get(string $name): ToolDefinition
    {
        return $this->tools[$name] ?? throw new ToolException(sprintf('Tool "%s" is not registered.', $name));
    }

    /**
     * @return list<ToolDefinition>
     */
    public function all(): array
    {
        return array_values($this->tools);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function describe(): array
    {
        return array_map(
            static fn (ToolDefinition $tool): array => $tool->describe(),
            $this->all(),
        );
    }
}
