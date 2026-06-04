<?php

declare(strict_types=1);

namespace Purple\Tests\Audit;

use Purple\Audit\WebhookAuditExporter;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use Purple\Domain\Audit\AuditExportRecord;
use Purple\Domain\EnterpriseContext;
use Purple\Security\PiiRedactor;
use Purple\Tests\Testing\TestCase;

final class WebhookAuditExporterTest extends TestCase
{
    public function testPostsRedactedAuditRecordToWebhook(): void
    {
        $captured = [];
        $exporter = new WebhookAuditExporter(
            endpoint: 'https://observability.internal/events',
            secrets: new class () implements SecretResolver {
                public function resolve(string $name): SecretValue
                {
                    return SecretValue::fromString('observability-token');
                }
            },
            redactor: new PiiRedactor(),
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return ['status' => 'ok'];
            },
        );

        $exporter->export(new AuditExportRecord(
            eventType: 'agent.tool.completed',
            runId: 'run-123',
            context: new EnterpriseContext('tenant-a', 'user-42', dataResidencyRegion: 'us'),
            payload: ['email' => 'customer@example.com'],
        ));

        $this->assertSame('POST', $captured['method'] ?? null);
        $this->assertSame('https://observability.internal/events', $captured['url'] ?? null);
        $this->assertSame('Bearer observability-token', $captured['headers']['Authorization'] ?? null);
        $payload = $captured['payload'] ?? null;
        $this->assertIsArray($payload);
        $recordPayload = $payload['payload'] ?? null;
        $this->assertIsArray($recordPayload);
        $this->assertSame('[redacted-email]', $recordPayload['email'] ?? null);
    }
}
