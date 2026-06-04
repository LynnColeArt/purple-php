<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Contracts\Runtime\DurableRunStore;
use Purple\Runtime\RuntimeException;

final readonly class SidecarDurableRunResumer
{
    public function __construct(
        private DurableRunStore $runs,
        private SidecarResumeClient $client,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function resume(
        string $runId,
        string $action = SidecarResumeRequest::ACTION_CONTINUE,
        array $metadata = [],
    ): SidecarResumeResponse {
        $record = $this->runs->get($runId);

        if ($record === null) {
            throw new RuntimeException(sprintf('Durable run "%s" was not found for sidecar resume.', $runId));
        }

        return $this->client->resume(SidecarResumeRequest::fromRecord($record, $action, $metadata));
    }
}
