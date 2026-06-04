<?php

declare(strict_types=1);

namespace Purple\Tests\Audit;

use Purple\Audit\FileAuditExporter;
use Purple\Domain\Audit\AuditExportRecord;
use Purple\Domain\EnterpriseContext;
use Purple\Security\PiiRedactor;
use Purple\Tests\Testing\TestCase;

final class FileAuditExporterTest extends TestCase
{
    public function testExportsRedactedEnterpriseAuditRecordAsJsonl(): void
    {
        $path = sys_get_temp_dir() . '/purple-audit-export-' . bin2hex(random_bytes(4)) . '.jsonl';
        $context = new EnterpriseContext('tenant-a', 'user-42', providerRoute: 'private-model', dataResidencyRegion: 'us');
        $record = new AuditExportRecord(
            eventType: 'agent.tool.completed',
            runId: 'run-123',
            context: $context,
            payload: ['email' => 'customer@example.com'],
        );

        (new FileAuditExporter($path, new PiiRedactor()))->export($record);

        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $payload = json_decode($lines[0] ?? '', true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload);
        $recordPayload = $payload['payload'] ?? null;
        $this->assertIsArray($recordPayload);
        $this->assertSame('tenant-a', $payload['tenant_id'] ?? null);
        $this->assertSame('us', $payload['data_residency_region'] ?? null);
        $this->assertSame('[redacted-email]', $recordPayload['email'] ?? null);
    }
}
