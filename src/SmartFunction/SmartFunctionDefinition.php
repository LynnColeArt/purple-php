<?php

declare(strict_types=1);

namespace Purple\SmartFunction;

use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Prompt\PromptTemplate;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Schema\SchemaValidator;
use Purple\Contracts\Security\DataRedactor;
use Purple\Contracts\SmartFunction\SmartFunction;

final readonly class SmartFunctionDefinition implements SmartFunction
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $name,
        public string $providerName,
        public string $model,
        public Provider $provider,
        public PromptTemplate $prompt,
        public SchemaValidator $validator,
        public string $outputSchema,
        public PolicyEngine $policy,
        public AuditLog $auditLog,
        public int $maxRetries = 0,
        public array $metadata = [],
        public ?DataRedactor $redactor = null,
    ) {
        if (trim($this->name) === '') {
            throw new SmartFunctionException('Smart function name must not be empty.');
        }

        if ($this->maxRetries < 0) {
            throw new SmartFunctionException('Smart function retries must not be negative.');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function run(mixed $input): mixed
    {
        return (new SmartFunctionRunner())->run($this, $input)->output;
    }
}
