<?php

declare(strict_types=1);

use Purple\Audit\FileAuditLog;
use Purple\ProviderProfile;
use Purple\Sdk;

require __DIR__ . '/../../vendor/autoload.php';

$sdk = Sdk::openAI(
    profile: ProviderProfile::openAI(
        model: 'gpt-4.1-mini',
        secretName: 'OPENAI_API_KEY',
    ),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/content.jsonl'),
);
$function = $sdk->smartFunction(
    name: 'content.brief',
    prompt: 'Create a JSON content brief for {{ topic }}.',
    outputSchema: '{"type":"object","required":["brief"],"properties":{"brief":{"type":"string"}}}',
);

print json_encode($function->run(['topic' => 'post-purchase onboarding']), JSON_PRETTY_PRINT) . PHP_EOL;
