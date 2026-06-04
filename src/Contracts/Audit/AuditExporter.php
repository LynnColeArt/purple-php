<?php

declare(strict_types=1);

namespace Purple\Contracts\Audit;

use Purple\Domain\Audit\AuditExportRecord;

interface AuditExporter
{
    public function export(AuditExportRecord $record): void;
}
