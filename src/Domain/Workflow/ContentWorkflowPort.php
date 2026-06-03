<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

use Purple\Domain\EnterpriseContext;

interface ContentWorkflowPort
{
    /**
     * @return list<ContentSearchResult>
     */
    public function searchContent(string $query, EnterpriseContext $context): array;

    public function draftContentRevision(string $contentId, string $instruction, EnterpriseContext $context): DraftRevision;
}
