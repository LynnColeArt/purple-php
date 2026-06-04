<?php

declare(strict_types=1);

namespace Purple\Runtime\Durable;

use DateTimeImmutable;
use Exception;
use Purple\Runtime\RuntimeException;

final readonly class DurableRunRecord
{
    /**
     * @param array<string, mixed> $state
     */
    public function __construct(
        public string $runId,
        public string $status,
        public array $state = [],
        public ?DateTimeImmutable $updatedAt = null,
    ) {
        if (trim($this->runId) === '') {
            throw new RuntimeException('Durable run ID must not be empty.');
        }

        if (trim($this->status) === '') {
            throw new RuntimeException('Durable run status must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'status' => $this->status,
            'state' => $this->state,
            'updated_at' => ($this->updatedAt ?? new DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $runId = $payload['run_id'] ?? null;
        $status = $payload['status'] ?? null;
        $state = $payload['state'] ?? [];
        $updatedAt = $payload['updated_at'] ?? null;

        if (! is_string($runId) || ! is_string($status)) {
            throw new RuntimeException('Durable run record requires string run_id and status fields.');
        }

        if (! is_array($state) || ($state !== [] && array_is_list($state))) {
            throw new RuntimeException('Durable run state must be an object.');
        }

        return new self(
            runId: $runId,
            status: $status,
            state: self::stringKeyedArray($state, 'Durable run state'),
            updatedAt: is_string($updatedAt) ? self::dateTime($updatedAt) : null,
        );
    }

    private static function dateTime(string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw new RuntimeException('Durable run updated_at is not a valid date.', 0, $exception);
        }
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
