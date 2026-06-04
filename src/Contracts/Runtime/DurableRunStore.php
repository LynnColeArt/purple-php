<?php

declare(strict_types=1);

namespace Purple\Contracts\Runtime;

use Purple\Runtime\Durable\DurableRunRecord;

interface DurableRunStore
{
    public function save(DurableRunRecord $record): void;

    public function get(string $runId): ?DurableRunRecord;
}
