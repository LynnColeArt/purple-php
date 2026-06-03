<?php

declare(strict_types=1);

use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolRegistry;
use Purple\Tool\ToolSideEffectLevel;

require __DIR__ . '/../../vendor/autoload.php';

$registry = new ToolRegistry([
    new ToolDefinition(
        name: 'catalog.lookup',
        description: 'Look up catalog metadata for a SKU.',
        inputSchema: '{"type":"object","required":["sku"],"properties":{"sku":{"type":"string"}}}',
        outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
        sideEffectLevel: ToolSideEffectLevel::Read,
        approvalRequired: false,
        maxRetries: 1,
        auditMetadata: ['domain' => 'catalog'],
    ),
]);

print json_encode($registry->describe(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
