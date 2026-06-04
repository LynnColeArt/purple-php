<?php

declare(strict_types=1);

namespace Purple\Agent;

final readonly class AgentToolCallRecord
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        public string $toolName,
        public array $input,
        public string $sideEffectLevel,
        public int $attempt,
        public string $status,
        public mixed $output = null,
        public ?string $error = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tool' => $this->toolName,
            'input' => $this->input,
            'side_effect_level' => $this->sideEffectLevel,
            'attempt' => $this->attempt,
            'status' => $this->status,
            'output' => $this->output,
            'error' => $this->error,
        ];
    }
}
