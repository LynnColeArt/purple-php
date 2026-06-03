<?php

declare(strict_types=1);

namespace Purple\Contracts\Audit;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AuditEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $type,
        public string $runId,
        public DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {
        if (trim($this->type) === '') {
            throw new InvalidArgumentException('Audit event type must not be empty.');
        }

        if (trim($this->runId) === '') {
            throw new InvalidArgumentException('Audit event run ID must not be empty.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function now(string $type, string $runId, array $metadata = []): self
    {
        return new self($type, $runId, new DateTimeImmutable(), $metadata);
    }
}
