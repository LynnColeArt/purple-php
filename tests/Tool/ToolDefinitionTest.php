<?php

declare(strict_types=1);

namespace Purple\Tests\Tool;

use Purple\Tests\Testing\TestCase;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolException;
use Purple\Tool\ToolRegistry;
use Purple\Tool\ToolSideEffectLevel;

final class ToolDefinitionTest extends TestCase
{
    public function testDeclaresAndInspectsToolMetadata(): void
    {
        $tool = new ToolDefinition(
            name: 'catalog.lookup',
            description: 'Look up catalog metadata for a SKU.',
            inputSchema: '{"type":"object","required":["sku"],"properties":{"sku":{"type":"string"}}}',
            outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
            sideEffectLevel: ToolSideEffectLevel::Read,
            approvalRequired: true,
            maxRetries: 2,
            approvalMetadata: ['mode' => 'human'],
            retryMetadata: ['backoff_ms' => 50],
            auditMetadata: ['domain' => 'catalog'],
        );
        $registry = new ToolRegistry([$tool]);
        $description = $registry->describe()[0];

        $this->assertSame($tool, $registry->get('catalog.lookup'));
        $this->assertSame('catalog.lookup', $description['name']);
        $this->assertSame('read', $description['side_effect_level']);
        $this->assertTrue($description['approval_required']);
        $this->assertSame(2, $description['max_retries']);
        $this->assertSame(['domain' => 'catalog'], $description['audit_metadata']);
    }

    public function testRejectsInvalidToolMetadata(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('Tool name must start with a letter');

        new ToolDefinition(
            name: '1.bad',
            description: 'Bad tool.',
            inputSchema: '{}',
            outputSchema: '{}',
        );
    }

    public function testRejectsDuplicateToolRegistration(): void
    {
        $tool = new ToolDefinition(
            name: 'catalog.lookup',
            description: 'Look up catalog metadata for a SKU.',
            inputSchema: '{}',
            outputSchema: '{}',
        );
        $registry = new ToolRegistry([$tool]);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('already registered');

        $registry->register($tool);
    }
}
