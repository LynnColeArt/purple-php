<?php

declare(strict_types=1);

namespace Purple\Runtime\Sidecar;

use Purple\Contracts\Runtime\SidecarTransport;

final readonly class CallbackSidecarTransport implements SidecarTransport
{
    /** @var callable(SidecarEnvelope): SidecarEnvelope */
    private mixed $handler;

    /**
     * @param callable(SidecarEnvelope): SidecarEnvelope $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function send(SidecarEnvelope $envelope): SidecarEnvelope
    {
        return ($this->handler)($envelope);
    }
}
