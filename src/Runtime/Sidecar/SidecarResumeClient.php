<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Contracts\Runtime\SidecarTransport;
use Purple\Runtime\RuntimeException;

final readonly class SidecarResumeClient
{
    public function __construct(
        private SidecarTransport $transport,
    ) {
    }

    public function resume(SidecarResumeRequest $request): SidecarResumeResponse
    {
        $responseEnvelope = $this->transport->send($request->toEnvelope());

        if ($responseEnvelope->runId !== $request->runId) {
            throw new RuntimeException('Sidecar resume response run ID did not match request.');
        }

        return SidecarResumeResponse::fromEnvelope($responseEnvelope);
    }
}
