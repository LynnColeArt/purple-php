<?php

declare(strict_types=1);

namespace Purple\Tests\Audit;

use Purple\Audit\FileAuditLog;
use Purple\Contracts\Audit\AuditEvent;
use Purple\Contracts\Security\SecretValue;
use Purple\Tests\Testing\TestCase;

final class FileAuditLogTest extends TestCase
{
    public function testWritesJsonLinesAndRedactsSensitiveMetadata(): void
    {
        $path = sys_get_temp_dir() . '/purple-audit-' . bin2hex(random_bytes(4)) . '.jsonl';
        $audit = new FileAuditLog($path);

        $audit->record(AuditEvent::now('smart_function.completed', 'run-123', [
            'provider' => 'openai',
            'api_key' => 'sk-raw-value',
            'secret' => SecretValue::fromString('sk-secret-value'),
        ]));

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);

        $payload = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload);
        $this->assertIsArray($payload['metadata']);
        $this->assertSame('smart_function.completed', $payload['type']);
        $this->assertSame('[redacted]', $payload['metadata']['api_key']);
        $this->assertSame('[redacted]', $payload['metadata']['secret']);
        $this->assertStringNotContainsString('sk-raw-value', $lines[0]);
        $this->assertStringNotContainsString('sk-secret-value', $lines[0]);
    }
}
