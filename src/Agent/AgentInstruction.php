<?php

declare(strict_types=1);

namespace Purple\Agent;

use JsonException;

final readonly class AgentInstruction
{
    /**
     * @param array<string, mixed> $input
     */
    private function __construct(
        public string $action,
        public ?string $toolName = null,
        public array $input = [],
        public ?string $answer = null,
    ) {
    }

    public static function complete(string $answer): self
    {
        return new self('complete', answer: $answer);
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function tool(string $name, array $input): self
    {
        return new self('tool', toolName: $name, input: $input);
    }

    public static function fromProviderContent(string $content): self
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AgentException('Agent provider output was not valid JSON: ' . $exception->getMessage(), 0, $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new AgentException('Agent provider output must be a JSON object.');
        }

        $action = $decoded['action'] ?? null;

        if ($action === 'complete') {
            $answer = $decoded['answer'] ?? null;

            if (! is_string($answer) || trim($answer) === '') {
                throw new AgentException('Complete agent instruction must include a non-empty answer.');
            }

            return self::complete($answer);
        }

        if ($action === 'tool') {
            $tool = $decoded['tool'] ?? null;
            $input = $decoded['input'] ?? [];

            if (! is_string($tool) || trim($tool) === '') {
                throw new AgentException('Tool agent instruction must include a tool name.');
            }

            if (! is_array($input) || array_is_list($input)) {
                throw new AgentException('Tool agent instruction input must be a JSON object.');
            }

            /** @var array<string, mixed> $input */
            return self::tool($tool, $input);
        }

        throw new AgentException('Agent provider output action must be "complete" or "tool".');
    }
}
