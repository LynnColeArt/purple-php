<?php

declare(strict_types=1);

namespace Purple\Contracts\Audit;

interface AuditLog
{
    public function record(AuditEvent $event): void;
}
