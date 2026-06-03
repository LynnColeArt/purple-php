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
    name: 'support.triage',
    providerName: 'fake',
    model: 'fake-model',
    provider: FakeProvider::replying('{"priority":"high","reason":"Mentions failed checkout."}'),
    prompt: new StringPromptTemplate('Triage this support note as JSON: {{ note }}'),
    validator: new JsonSchemaValidator(),
    outputSchema: '{"type":"object","required":["priority","reason"],"properties":{"priority":{"type":"string"},"reason":{"type":"string"}}}',
    policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/support.jsonl'),
);

print json_encode($function->run(['note' => 'Customer cannot complete checkout.']), JSON_PRETTY_PRINT) . PHP_EOL;
