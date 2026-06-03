<?php

declare(strict_types=1);

namespace Purple\Domain\Audit;

use DateTimeImmutable;
use Purple\Domain\EnterpriseContext;

final readonly class AuditExportRecord
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $eventType,
        public string $runId,
        public EnterpriseContext $context,
        public array $payload = [],
        public ?DateTimeImmutable $occurredAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toExportPayload(): array
    {
        return [
            'event_type' => $this->eventType,
            'run_id' => $this->runId,
            'tenant_id' => $this->context->tenantId,
            'user_id' => $this->context->userId,
            'data_sensitivity' => $this->context->dataSensitivity->value,
            'retention_days' => $this->context->retentionDays,
            'provider_route' => $this->context->providerRoute,
            'occurred_at' => ($this->occurredAt ?? new DateTimeImmutable())->format(DATE_ATOM),
            'payload' => $this->payload,
        ];
    }
}
