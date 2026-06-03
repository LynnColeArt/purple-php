<?php

declare(strict_types=1);

use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatMessage;
use Purple\Chat\ChatSession;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Policy\BasicPolicyEngine;
use Purple\Testing\FakeProvider;

require __DIR__ . '/../../vendor/autoload.php';

$history = new ChatHistory([
    ChatMessage::system('Answer as an ecommerce support assistant.'),
]);
$session = new ChatSession(
    name: 'support.chat',
    providerName: 'fake',
    model: 'fake-model',
    provider: new FakeProvider([
        new ProviderResponse('Try clearing the cart and re-applying the discount code.'),
    ]),
    policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
    auditLog: new FileAuditLog(__DIR__ . '/../../var/audit/chat.jsonl'),
    history: $history,
);

$response = $session->send('The discount code fails at checkout.');

print json_encode([
    'assistant' => $response->content,
    'message_count' => $response->history->count(),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
