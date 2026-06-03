<?php

declare(strict_types=1);

use Purple\Audit\FileAuditLog;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;

require __DIR__ . '/../../vendor/autoload.php';

$function = new SmartFunctionDefinition(
    name: 'catalog.summary',
    providerName: 'fake',
    model: 'fake-model',
    provider: FakeProvider::replying('{"summary":"A concise product summary for merchandisers."}'),
    prompt: new StringPromptTemplate('Summarize {{ title }} for a product catalog as JSON.'),
    validator: new JsonSchemaValidator(),
    outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
    policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/catalog.jsonl'),
);

print json_encode($function->run(['title' => 'Merino travel cardigan']), JSON_PRETTY_PRINT) . PHP_EOL;
