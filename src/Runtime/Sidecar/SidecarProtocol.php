<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use JsonException;
use Purple\Runtime\RuntimeException;

final readonly class SidecarProtocol
{
    public const VERSION = 'purple.sidecar.v1';

    public function encode(SidecarEnvelope $envelope): string
    {
        if ($envelope->version !== self::VERSION) {
            throw new RuntimeException('Unsupported sidecar envelope version.');
        }

        try {
            return json_encode($envelope->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode sidecar envelope: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function decode(string $raw): SidecarEnvelope
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to decode sidecar envelope: ' . $exception->getMessage(), 0, $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Sidecar envelope must be a JSON object.');
        }

        $envelope = SidecarEnvelope::fromArray($this->stringKeyedArray($decoded, 'Sidecar envelope'));

        if ($envelope->version !== self::VERSION) {
            throw new RuntimeException('Unsupported sidecar envelope version.');
        }

        return $envelope;
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $payload, string $label): array
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
