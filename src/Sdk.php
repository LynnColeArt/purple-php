<?php

declare(strict_types=1);

namespace Purple;

use InvalidArgumentException;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatSession;
use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Schema\SchemaValidator;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\SmartFunction\SmartFunctionDefinition;

final readonly class Sdk
{
    private AuditLog $auditLog;

    private PolicyEngine $policy;

    private SchemaValidator $validator;

    public function __construct(
        private Provider $provider,
        private string $providerName,
        private string $model,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
    ) {
        $this->assertNonEmpty($this->providerName, 'Provider name');
        $this->assertNonEmpty($this->model, 'Model');

        $this->auditLog = $auditLog ?? new FileAuditLog($this->defaultAuditPath());
        $this->policy = $policy ?? new BasicPolicyEngine(
            allowedProviders: [$this->providerName],
            allowedModels: [$this->model],
        );
        $this->validator = $validator ?? new JsonSchemaValidator();
    }

    public function smartFunction(
        string $name,
        string $prompt,
        string $outputSchema,
        int $maxRetries = 0,
    ): SmartFunctionDefinition {
        return new SmartFunctionDefinition(
            name: $name,
            providerName: $this->providerName,
            model: $this->model,
            provider: $this->provider,
            prompt: new StringPromptTemplate($prompt),
            validator: $this->validator,
            outputSchema: $outputSchema,
            policy: $this->policy,
            auditLog: $this->auditLog,
            maxRetries: $maxRetries,
        );
    }

    public function chatSession(string $name, ?ChatHistory $history = null): ChatSession
    {
        return new ChatSession(
            name: $name,
            providerName: $this->providerName,
            model: $this->model,
            provider: $this->provider,
            policy: $this->policy,
            auditLog: $this->auditLog,
            history: $history,
        );
    }

    private function assertNonEmpty(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($label . ' must not be empty.');
        }
    }

    private function defaultAuditPath(): string
    {
        $basePath = getcwd();

        if ($basePath === false) {
            $basePath = sys_get_temp_dir();
        }

        return $basePath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'purple.jsonl';
    }
}
