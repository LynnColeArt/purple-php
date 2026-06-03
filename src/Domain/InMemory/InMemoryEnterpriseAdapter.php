<?php

declare(strict_types=1);

namespace Purple\Domain\InMemory;

use Purple\Approval\ApprovalDecision;
use Purple\Approval\ApprovalRequest;
use Purple\Domain\Audit\AuditExportRecord;
use Purple\Domain\EnterpriseContext;
use Purple\Domain\Workflow\ApprovalWorkflowPort;
use Purple\Domain\Workflow\CatalogItem;
use Purple\Domain\Workflow\CatalogWorkflowPort;
use Purple\Domain\Workflow\ContentSearchResult;
use Purple\Domain\Workflow\ContentWorkflowPort;
use Purple\Domain\Workflow\DraftRevision;
use Purple\Domain\Workflow\ExternalAuditPort;
use Purple\Domain\Workflow\OrderSummary;
use Purple\Domain\Workflow\OrderWorkflowPort;
use Purple\Domain\Workflow\SupportClassification;
use Purple\Domain\Workflow\SupportWorkflowPort;

final class InMemoryEnterpriseAdapter implements ContentWorkflowPort, CatalogWorkflowPort, OrderWorkflowPort, SupportWorkflowPort, ApprovalWorkflowPort, ExternalAuditPort
{
    /** @var list<AuditExportRecord> */
    private array $auditRecords = [];

    /**
     * @return list<ContentSearchResult>
     */
    public function searchContent(string $query, EnterpriseContext $context): array
    {
        return [
            new ContentSearchResult('content-1', 'Return policy', 'Current customer-facing return policy.'),
        ];
    }

    public function draftContentRevision(string $contentId, string $instruction, EnterpriseContext $context): DraftRevision
    {
        return new DraftRevision($contentId, 'Draft content revision for ' . $context->tenantId, [
            'instruction' => $instruction,
        ]);
    }

    /**
     * @return list<CatalogItem>
     */
    public function searchCatalog(string $query, EnterpriseContext $context): array
    {
        return [
            new CatalogItem('SKU-1', 'Merino travel cardigan', 'active'),
        ];
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function draftCatalogUpdate(string $sku, array $changes, EnterpriseContext $context): DraftRevision
    {
        return new DraftRevision($sku, 'Draft catalog update for ' . $sku, $changes);
    }

    public function lookupOrder(string $orderId, EnterpriseContext $context): OrderSummary
    {
        return new OrderSummary($orderId, 'Customer ' . $context->userId, 'paid', 129.95);
    }

    public function classifyTicket(string $subject, string $body, EnterpriseContext $context): SupportClassification
    {
        $priority = str_contains(strtolower($subject . ' ' . $body), 'checkout') ? 'high' : 'normal';

        return new SupportClassification($priority, 'commerce-support', 'Fixture-backed classifier for enterprise workflow demos.');
    }

    public function requestApproval(ApprovalRequest $request, EnterpriseContext $context): ApprovalDecision
    {
        return ApprovalDecision::approve('Approved for tenant ' . $context->tenantId . '.');
    }

    public function recordExternalAuditEvent(AuditExportRecord $record): void
    {
        $this->auditRecords[] = $record;
    }

    /**
     * @return list<AuditExportRecord>
     */
    public function auditRecords(): array
    {
        return $this->auditRecords;
    }
}
