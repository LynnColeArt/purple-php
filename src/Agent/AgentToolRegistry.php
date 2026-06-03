<?php

declare(strict_types=1);

namespace Purple\Agent;

final class AgentToolRegistry
{
    /** @var array<string, AgentTool> */
    private array $tools = [];

    /**
     * @param list<AgentTool> $tools
     */
    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(AgentTool $tool): void
    {
        if (isset($this->tools[$tool->name()])) {
            throw new AgentException(sprintf('Agent tool "%s" is already registered.', $tool->name()));
        }

        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): AgentTool
    {
        return $this->tools[$name] ?? throw new AgentException(sprintf('Agent tool "%s" is not registered.', $name));
    }
}
