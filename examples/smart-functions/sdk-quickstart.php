<?php

declare(strict_types=1);

use Purple\Audit\FileAuditLog;
use Purple\Sdk;
use Purple\Testing\FakeProvider;

require __DIR__ . '/../../vendor/autoload.php';

$sdk = new Sdk(
    provider: FakeProvider::replying('{"summary":"A concise SDK quickstart summary."}'),
    providerName: 'fake',
    model: 'fake-model',
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/sdk-quickstart.jsonl'),
);

$summary = $sdk->smartFunction(
    name: 'catalog.summary',
    prompt: 'Summarize {{ title }} for a product catalog as JSON.',
    outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
)->run(['title' => 'Merino travel cardigan']);

print json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
