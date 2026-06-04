<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Runtime\RuntimeException;

final readonly class SidecarResumeResponse
{
    public const TYPE = 'agent.run.resume.response';

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $runId,
        public string $status,
        public ?string $message = null,
        public array $metadata = [],
    ) {
        if (trim($this->runId) === '') {
            throw new RuntimeException('Sidecar resume response run ID must not be empty.');
        }

        if (trim($this->status) === '') {
            throw new RuntimeException('Sidecar resume response status must not be empty.');
        }
    }

    public function toEnvelope(): SidecarEnvelope
    {
        return new SidecarEnvelope(
            version: SidecarProtocol::VERSION,
            type: self::TYPE,
            runId: $this->runId,
            payload: [
                'status' => $this->status,
                'message' => $this->message,
                'metadata' => $this->metadata,
            ],
        );
    }

    public static function fromEnvelope(SidecarEnvelope $envelope): self
    {
        if ($envelope->type !== self::TYPE) {
            throw new RuntimeException(sprintf('Expected sidecar resume response envelope, got "%s".', $envelope->type));
        }

        $status = $envelope->payload['status'] ?? null;
        $message = $envelope->payload['message'] ?? null;
        $metadata = $envelope->payload['metadata'] ?? [];

        if (! is_string($status)) {
            throw new RuntimeException('Sidecar resume response status must be a string.');
        }

        if ($message !== null && ! is_string($message)) {
            throw new RuntimeException('Sidecar resume response message must be a string when provided.');
        }

        if (! is_array($metadata) || ($metadata !== [] && array_is_list($metadata))) {
            throw new RuntimeException('Sidecar resume response metadata must be an object.');
        }

        return new self(
            runId: $envelope->runId,
            status: $status,
            message: $message,
            metadata: self::stringKeyedArray($metadata, 'Sidecar resume response metadata'),
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
