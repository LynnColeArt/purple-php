<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Domain\Audit\AuditExportRecord;

interface ExternalAuditPort
{
    public function recordExternalAuditEvent(AuditExportRecord $record): void;
}
