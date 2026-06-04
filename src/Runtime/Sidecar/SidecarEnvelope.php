<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Runtime\RuntimeException;

final readonly class SidecarEnvelope
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $version,
        public string $type,
        public string $runId,
        public array $payload = [],
    ) {
        if (trim($this->version) === '') {
            throw new RuntimeException('Sidecar envelope version must not be empty.');
        }

        if (trim($this->type) === '') {
            throw new RuntimeException('Sidecar envelope type must not be empty.');
        }

        if (trim($this->runId) === '') {
            throw new RuntimeException('Sidecar envelope run ID must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'type' => $this->type,
            'run_id' => $this->runId,
            'payload' => $this->payload,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $type = $payload['type'] ?? null;
        $runId = $payload['run_id'] ?? null;
        $envelopePayload = $payload['payload'] ?? [];

        if (! is_string($version) || ! is_string($type) || ! is_string($runId)) {
            throw new RuntimeException('Sidecar envelope requires string version, type, and run_id fields.');
        }

        if (! is_array($envelopePayload) || ($envelopePayload !== [] && array_is_list($envelopePayload))) {
            throw new RuntimeException('Sidecar envelope payload must be an object.');
        }

        return new self(
            version: $version,
            type: $type,
            runId: $runId,
            payload: self::stringKeyedArray($envelopePayload, 'Sidecar envelope payload'),
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
