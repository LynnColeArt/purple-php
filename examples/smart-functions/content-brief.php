<?php

declare(strict_types=1);

use Purple\Audit\FileAuditLog;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\Security\EnvironmentSecretResolver;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Provider\OpenAI\OpenAIProvider;

require __DIR__ . '/../../vendor/autoload.php';

$provider = new OpenAIProvider(new EnvironmentSecretResolver());
$function = new SmartFunctionDefinition(
    name: 'content.brief',
    providerName: 'openai',
    model: 'gpt-4.1-mini',
    provider: $provider,
    prompt: new StringPromptTemplate('Create a JSON content brief for {{ topic }}.'),
    validator: new JsonSchemaValidator(),
    outputSchema: '{"type":"object","required":["brief"],"properties":{"brief":{"type":"string"}}}',
    policy: new BasicPolicyEngine(allowedProviders: ['openai'], allowedModels: ['gpt-4.1-mini'], maxRuns: 5),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/content.jsonl'),
);

print json_encode($function->run(['topic' => 'post-purchase onboarding']), JSON_PRETTY_PRINT) . PHP_EOL;
