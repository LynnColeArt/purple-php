<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Contracts\Runtime\DurableRunStore;
use Purple\Runtime\RuntimeException;

final readonly class SidecarRuntimeService
{
    public function __construct(
        private DurableRunStore $runs,
        private string $nodeId = 'local-sidecar',
        private SidecarProtocol $protocol = new SidecarProtocol(),
    ) {
        if (trim($this->nodeId) === '') {
            throw new RuntimeException('Sidecar runtime node ID must not be empty.');
        }
    }

    public function handle(string $rawEnvelope): string
    {
        $request = SidecarResumeRequest::fromEnvelope($this->protocol->decode($rawEnvelope));

        return $this->protocol->encode($this->resume($request)->toEnvelope());
    }

    public function resume(SidecarResumeRequest $request): SidecarResumeResponse
    {
        if ($request->action !== SidecarResumeRequest::ACTION_CONTINUE) {
            return $this->rejected(
                request: $request,
                reason: 'unsupported_action',
                message: 'Unsupported sidecar resume action.',
            );
        }

        $record = $this->runs->get($request->runId);

        if ($record === null) {
            return $this->rejected(
                request: $request,
                reason: 'missing_run',
                message: 'Durable run was not found for sidecar resume.',
            );
        }

        $metadata = [
            ...$this->metadata($request),
            'reason' => 'accepted',
            'record_status' => $record->status,
        ];

        if ($record->updatedAt !== null) {
            $metadata['record_updated_at'] = $record->updatedAt->format(DATE_ATOM);
        }

        return new SidecarResumeResponse(
            runId: $request->runId,
            status: 'accepted',
            message: 'Resume request accepted by local sidecar runtime.',
            metadata: $metadata,
        );
    }

    private function rejected(SidecarResumeRequest $request, string $reason, string $message): SidecarResumeResponse
    {
        return new SidecarResumeResponse(
            runId: $request->runId,
            status: 'rejected',
            message: $message,
            metadata: [
                ...$this->metadata($request),
                'reason' => $reason,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(SidecarResumeRequest $request): array
    {
        return [
            'sidecar_node' => $this->nodeId,
            'action' => $request->action,
            'state_pointer' => $request->statePointer,
        ];
    }
}
