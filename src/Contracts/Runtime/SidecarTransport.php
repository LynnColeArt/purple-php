<?php

declare(strict_types=1);

namespace Purple\Contracts\Runtime;

use Purple\Runtime\Sidecar\SidecarEnvelope;

interface SidecarTransport
{
    public function send(SidecarEnvelope $envelope): SidecarEnvelope;
}
