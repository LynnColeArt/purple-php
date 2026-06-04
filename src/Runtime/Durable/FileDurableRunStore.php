<?php

declare(strict_types=1);

namespace Purple\Runtime\Durable;

use JsonException;
use Purple\Contracts\Runtime\DurableRunStore;
use Purple\Runtime\RuntimeException;

final readonly class FileDurableRunStore implements DurableRunStore
{
    public function __construct(
        private string $directory,
    ) {
    }

    public function save(DurableRunRecord $record): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0775, true) && ! is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Unable to create durable run directory "%s".', $this->directory));
        }

        try {
            $encoded = json_encode($record->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode durable run record: ' . $exception->getMessage(), 0, $exception);
        }

        if (file_put_contents($this->path($record->runId), $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write durable run "%s".', $record->runId));
        }
    }

    public function get(string $runId): ?DurableRunRecord
    {
        $path = $this->path($runId);

        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException(sprintf('Unable to read durable run "%s".', $runId));
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to decode durable run record: ' . $exception->getMessage(), 0, $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Durable run record must be a JSON object.');
        }

        return DurableRunRecord::fromArray($this->stringKeyedArray($decoded, 'Durable run record'));
    }

    private function path(string $runId): string
    {
        if (preg_match('/^[A-Za-z0-9_.-]+$/', $runId) !== 1) {
            throw new RuntimeException('Durable run ID may only contain letters, numbers, dots, dashes, and underscores.');
        }

        return rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $runId . '.json';
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
