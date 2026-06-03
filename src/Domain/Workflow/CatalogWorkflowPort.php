<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Domain\EnterpriseContext;

interface CatalogWorkflowPort
{
    /**
     * @return list<CatalogItem>
     */
    public function searchCatalog(string $query, EnterpriseContext $context): array;

    /**
     * @param array<string, mixed> $changes
     */
    public function draftCatalogUpdate(string $sku, array $changes, EnterpriseContext $context): DraftRevision;
}
