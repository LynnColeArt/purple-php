<?php

declare(strict_types=1);

namespace Purple\Tool;

use JsonException;
use stdClass;

final readonly class ToolDefinition
{
    /**
     * @param array<string, mixed> $approvalMetadata
     * @param array<string, mixed> $retryMetadata
     * @param array<string, mixed> $auditMetadata
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $inputSchema,
        public string $outputSchema,
        public ToolSideEffectLevel $sideEffectLevel = ToolSideEffectLevel::None,
        public bool $approvalRequired = false,
        public int $maxRetries = 0,
        public array $approvalMetadata = [],
        public array $retryMetadata = [],
        public array $auditMetadata = [],
    ) {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/', $this->name) !== 1) {
            throw new ToolException('Tool name must start with a letter and contain only letters, numbers, dots, dashes, or underscores.');
        }

        if (trim($this->description) === '') {
            throw new ToolException('Tool description must not be empty.');
        }

        if ($this->maxRetries < 0) {
            throw new ToolException('Tool max retries must not be negative.');
        }

        $this->validateSchema($this->inputSchema, 'input');
        $this->validateSchema($this->outputSchema, 'output');
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'input_schema' => $this->decodeSchema($this->inputSchema),
            'output_schema' => $this->decodeSchema($this->outputSchema),
            'side_effect_level' => $this->sideEffectLevel->value,
            'approval_required' => $this->approvalRequired,
            'max_retries' => $this->maxRetries,
            'approval_metadata' => $this->approvalMetadata,
            'retry_metadata' => $this->retryMetadata,
            'audit_metadata' => $this->auditMetadata,
        ];
    }

    private function validateSchema(string $schema, string $label): void
    {
        $this->decodeSchema($schema, $label);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSchema(string $schema, string $label = 'schema'): array
    {
        try {
            $object = json_decode($schema, false, 512, JSON_THROW_ON_ERROR);
            $decoded = json_decode($schema, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ToolException(sprintf('Tool %s schema is not valid JSON: %s', $label, $exception->getMessage()), 0, $exception);
        }

        if (! $object instanceof stdClass || ! is_array($decoded)) {
            throw new ToolException(sprintf('Tool %s schema must be a JSON object.', $label));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
