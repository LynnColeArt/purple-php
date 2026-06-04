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
use Purple\Contracts\Security\DataRedactor;
use Purple\Contracts\Security\SecretResolver;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Provider\Azure\AzureOpenAIProvider;
use Purple\Provider\Bedrock\BedrockProvider;
use Purple\Provider\OpenAI\OpenAIProvider;
use Purple\Provider\Sidecar\SidecarProvider;
use Purple\Schema\JsonSchemaValidator;
use Purple\Security\EnvironmentSecretResolver;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;

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

        $this->auditLog = $auditLog ?? new FileAuditLog(self::defaultAuditPath());
        $this->policy = $policy ?? new BasicPolicyEngine(
            allowedProviders: [$this->providerName],
            allowedModels: [$this->model],
        );
        $this->validator = $validator ?? new JsonSchemaValidator();
    }

    public static function fromProvider(
        Provider $provider,
        ProviderProfile $profile,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
    ): self {
        return new self(
            provider: $provider,
            providerName: $profile->providerName,
            model: $profile->model,
            auditLog: $auditLog ?? new FileAuditLog($profile->auditPath ?? self::defaultAuditPath()),
            policy: $policy ?? $profile->policy(),
            validator: $validator,
        );
    }

    public static function fake(
        ?ProviderProfile $profile = null,
        ?FakeProvider $provider = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
    ): self {
        $profile ??= ProviderProfile::fake();
        self::assertProfileProvider($profile, 'fake', 'Fake');

        return self::fromProvider(
            provider: $provider ?? new FakeProvider(),
            profile: $profile,
            auditLog: $auditLog,
            policy: $policy,
            validator: $validator,
        );
    }

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public static function openAI(
        ?ProviderProfile $profile = null,
        ?SecretResolver $secrets = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
        ?callable $transport = null,
    ): self {
        $profile ??= ProviderProfile::openAI();
        self::assertProfileProvider($profile, 'openai', 'OpenAI');

        if ($profile->secretName === null) {
            throw new InvalidArgumentException('OpenAI provider profile must define a secret name.');
        }

        return self::fromProvider(
            provider: new OpenAIProvider(
                secrets: $secrets ?? new EnvironmentSecretResolver(),
                secretName: $profile->secretName,
                transport: $transport,
            ),
            profile: $profile,
            auditLog: $auditLog,
            policy: $policy,
            validator: $validator,
        );
    }

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public static function azureOpenAI(
        string $resource,
        ?ProviderProfile $profile = null,
        ?SecretResolver $secrets = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
        ?callable $transport = null,
        string $apiVersion = '2024-02-15-preview',
    ): self {
        $profile ??= ProviderProfile::azureOpenAI();
        self::assertProfileProvider($profile, 'azure_openai', 'Azure OpenAI');

        if ($profile->secretName === null) {
            throw new InvalidArgumentException('Azure OpenAI provider profile must define a secret name.');
        }

        return self::fromProvider(
            provider: new AzureOpenAIProvider(
                secrets: $secrets ?? new EnvironmentSecretResolver(),
                resource: $resource,
                deployment: $profile->model,
                secretName: $profile->secretName,
                apiVersion: $apiVersion,
                transport: $transport,
            ),
            profile: $profile,
            auditLog: $auditLog,
            policy: $policy,
            validator: $validator,
        );
    }

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public static function bedrock(
        ?ProviderProfile $profile = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
        ?callable $transport = null,
        string $region = 'us-east-1',
    ): self {
        $profile ??= ProviderProfile::bedrock();
        self::assertProfileProvider($profile, 'bedrock', 'Bedrock');

        return self::fromProvider(
            provider: new BedrockProvider(
                region: $region,
                transport: $transport,
            ),
            profile: $profile,
            auditLog: $auditLog,
            policy: $policy,
            validator: $validator,
        );
    }

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public static function sidecar(
        string $endpoint,
        ?ProviderProfile $profile = null,
        ?SecretResolver $secrets = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
        ?callable $transport = null,
    ): self {
        $profile ??= ProviderProfile::sidecar();
        self::assertProfileProvider($profile, 'sidecar', 'Sidecar');

        return self::fromProvider(
            provider: new SidecarProvider(
                endpoint: $endpoint,
                secrets: $profile->secretName === null ? null : ($secrets ?? new EnvironmentSecretResolver()),
                secretName: $profile->secretName ?? 'PURPLE_SIDECAR_TOKEN',
                transport: $transport,
            ),
            profile: $profile,
            auditLog: $auditLog,
            policy: $policy,
            validator: $validator,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function smartFunction(
        string $name,
        string $prompt,
        string $outputSchema,
        int $maxRetries = 0,
        array $metadata = [],
        ?DataRedactor $redactor = null,
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
            metadata: $metadata,
            redactor: $redactor,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function chatSession(string $name, ?ChatHistory $history = null, array $metadata = [], ?DataRedactor $redactor = null): ChatSession
    {
        return new ChatSession(
            name: $name,
            providerName: $this->providerName,
            model: $this->model,
            provider: $this->provider,
            policy: $this->policy,
            auditLog: $this->auditLog,
            history: $history,
            metadata: $metadata,
            redactor: $redactor,
        );
    }

    private function assertNonEmpty(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($label . ' must not be empty.');
        }
    }

    private static function assertProfileProvider(ProviderProfile $profile, string $providerName, string $factoryLabel): void
    {
        if ($profile->providerName !== $providerName) {
            throw new InvalidArgumentException(sprintf(
                '%s SDK factory requires provider profile "%s"; received "%s".',
                $factoryLabel,
                $providerName,
                $profile->providerName,
            ));
        }
    }

    private static function defaultAuditPath(): string
    {
        $basePath = getcwd();

        if ($basePath === false) {
            $basePath = sys_get_temp_dir();
        }

        return $basePath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'purple.jsonl';
    }
}
