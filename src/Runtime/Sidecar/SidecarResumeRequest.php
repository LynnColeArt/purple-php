<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\RuntimeException;

final readonly class SidecarResumeRequest
{
    public const TYPE = 'agent.run.resume.request';
    public const ACTION_CONTINUE = 'continue';

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $runId,
        public string $action = self::ACTION_CONTINUE,
        public ?string $statePointer = null,
        public ?string $status = null,
        public array $metadata = [],
    ) {
        if (trim($this->runId) === '') {
            throw new RuntimeException('Sidecar resume run ID must not be empty.');
        }

        if (trim($this->action) === '') {
            throw new RuntimeException('Sidecar resume action must not be empty.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function fromRecord(
        DurableRunRecord $record,
        string $action = self::ACTION_CONTINUE,
        array $metadata = [],
    ): self {
        return new self(
            runId: $record->runId,
            action: $action,
            statePointer: 'durable-run:' . $record->runId,
            status: $record->status,
            metadata: $metadata,
        );
    }

    public function toEnvelope(): SidecarEnvelope
    {
        return new SidecarEnvelope(
            version: SidecarProtocol::VERSION,
            type: self::TYPE,
            runId: $this->runId,
            payload: [
                'action' => $this->action,
                'state_pointer' => $this->statePointer,
                'status' => $this->status,
                'metadata' => $this->metadata,
            ],
        );
    }

    public static function fromEnvelope(SidecarEnvelope $envelope): self
    {
        if ($envelope->type !== self::TYPE) {
            throw new RuntimeException(sprintf('Expected sidecar resume request envelope, got "%s".', $envelope->type));
        }

        $action = $envelope->payload['action'] ?? null;
        $statePointer = $envelope->payload['state_pointer'] ?? null;
        $status = $envelope->payload['status'] ?? null;
        $metadata = $envelope->payload['metadata'] ?? [];

        if (! is_string($action)) {
            throw new RuntimeException('Sidecar resume request action must be a string.');
        }

        if ($statePointer !== null && ! is_string($statePointer)) {
            throw new RuntimeException('Sidecar resume request state_pointer must be a string when provided.');
        }

        if ($status !== null && ! is_string($status)) {
            throw new RuntimeException('Sidecar resume request status must be a string when provided.');
        }

        if (! is_array($metadata) || ($metadata !== [] && array_is_list($metadata))) {
            throw new RuntimeException('Sidecar resume request metadata must be an object.');
        }

        return new self(
            runId: $envelope->runId,
            action: $action,
            statePointer: $statePointer,
            status: $status,
            metadata: self::stringKeyedArray($metadata, 'Sidecar resume request metadata'),
        );
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function stringKeyedArray(array $payload, string $label): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                throw new RuntimeException($label . ' must use string keys.');
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
