<?php

declare(strict_types=1);

namespace Purple\Tests\SmartFunction;

use Purple\Audit\FileAuditLog;
use Purple\Domain\EnterpriseContext;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\Security\PiiRedactor;
use Purple\SmartFunction\PolicyDenied;
use Purple\SmartFunction\SchemaValidationFailed;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\SmartFunction\SmartFunctionRunner;
use Purple\Testing\FakeProvider;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Tests\Testing\TestCase;

final class SmartFunctionRunnerTest extends TestCase
{
    private const OUTPUT_SCHEMA = <<<'JSON'
{
  "type": "object",
  "required": ["summary"],
  "properties": {
    "summary": {"type": "string"}
  }
}
JSON;

    public function testRunsSmartFunctionWithFakeProvider(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-smart-function-' . bin2hex(random_bytes(4)) . '.jsonl';
        $provider = FakeProvider::replying('{"summary":"Red jacket for winter catalog."}');
        $function = $this->function($provider, $auditPath);

        $result = (new SmartFunctionRunner())->run($function, [
            'title' => 'Red jacket',
        ]);

        $this->assertSame(['summary' => 'Red jacket for winter catalog.'], $result->output);
        $this->assertTrue($result->validation->valid);
        $this->assertSame(1, $result->attempts);
        $this->assertCount(1, $provider->requests());

        $audit = implode("\n", file($auditPath, FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertStringContainsString('smart_function.started', $audit);
        $this->assertStringContainsString('smart_function.completed', $audit);
        $this->assertStringContainsString('"validation_status":"passed"', $audit);
    }

    public function testPolicyDenialBlocksProviderInvocation(): void
    {
        $provider = FakeProvider::replying('{"summary":"Should not run."}');
        $function = $this->function(
            $provider,
            sys_get_temp_dir() . '/purple-policy-' . bin2hex(random_bytes(4)) . '.jsonl',
            new BasicPolicyEngine(allowedProviders: ['openai']),
        );

        $this->expectException(PolicyDenied::class);
        $this->expectExceptionMessage('not allowed');

        try {
            (new SmartFunctionRunner())->run($function, ['title' => 'Red jacket']);
        } finally {
            $this->assertSame([], $provider->requests());
        }
    }

    public function testRetriesValidationFailure(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('{"wrong":"shape"}'),
            new ProviderResponse('{"summary":"Recovered."}'),
        ]);
        $function = $this->function(
            $provider,
            sys_get_temp_dir() . '/purple-retry-' . bin2hex(random_bytes(4)) . '.jsonl',
            maxRetries: 1,
        );

        $result = (new SmartFunctionRunner())->run($function, ['title' => 'Red jacket']);

        $this->assertSame(['summary' => 'Recovered.'], $result->output);
        $this->assertSame(2, $result->attempts);
        $this->assertCount(2, $provider->requests());
    }

    public function testRetriesInvalidJsonOutput(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('not json'),
            new ProviderResponse('{"summary":"Recovered from invalid JSON."}'),
        ]);
        $function = $this->function(
            $provider,
            sys_get_temp_dir() . '/purple-invalid-json-retry-' . bin2hex(random_bytes(4)) . '.jsonl',
            maxRetries: 1,
        );

        $result = (new SmartFunctionRunner())->run($function, ['title' => 'Red jacket']);

        $this->assertSame(['summary' => 'Recovered from invalid JSON.'], $result->output);
        $this->assertSame(2, $result->attempts);
        $this->assertCount(2, $provider->requests());
    }

    public function testValidationFailureIsClear(): void
    {
        $function = $this->function(
            FakeProvider::replying('{"wrong":"shape"}'),
            sys_get_temp_dir() . '/purple-validation-' . bin2hex(random_bytes(4)) . '.jsonl',
        );

        $this->expectException(SchemaValidationFailed::class);
        $this->expectExceptionMessage('$.summary is required');

        (new SmartFunctionRunner())->run($function, ['title' => 'Red jacket']);
    }

    public function testInvalidJsonFailureIsAudited(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-invalid-json-audit-' . bin2hex(random_bytes(4)) . '.jsonl';
        $provider = FakeProvider::replying('not json');
        $function = $this->function($provider, $auditPath);

        try {
            (new SmartFunctionRunner())->run($function, ['title' => 'Red jacket']);
            $this->fail('Expected invalid JSON output to fail schema validation.');
        } catch (SchemaValidationFailed $exception) {
            $this->assertStringContainsString('Provider output was not valid JSON', $exception->getMessage());
        }

        $this->assertCount(1, $provider->requests());

        $audit = implode("\n", file($auditPath, FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertStringContainsString('smart_function.failed', $audit);
        $this->assertStringContainsString('"status":"validation_failed"', $audit);
        $this->assertStringContainsString('"validation_status":"failed"', $audit);
        $this->assertStringContainsString('Provider output was not valid JSON', $audit);
    }

    public function testEnterpriseMetadataAndRedactionReachProviderRequest(): void
    {
        $context = new EnterpriseContext('tenant-a', 'user-42', providerRoute: 'private-model', dataResidencyRegion: 'us');
        $provider = FakeProvider::replying('{"summary":"Redacted output."}');
        $function = $this->function(
            $provider,
            sys_get_temp_dir() . '/purple-enterprise-smart-' . bin2hex(random_bytes(4)) . '.jsonl',
            new BasicPolicyEngine(
                allowedProviders: ['fake'],
                allowedModels: ['fake-model'],
                allowedTenantIds: ['tenant-a'],
                allowedProviderRoutes: ['private-model'],
                allowedDataResidencyRegions: ['us'],
            ),
            metadata: $context->policyMetadata(),
            redactor: new PiiRedactor(),
        );

        (new SmartFunctionRunner())->run($function, [
            'title' => 'Email customer@example.com about order.',
        ]);

        $request = $provider->requests()[0];

        $this->assertSame('tenant-a', $request->metadata['tenant_id'] ?? null);
        $this->assertSame('private-model', $request->metadata['provider_route'] ?? null);
        $this->assertSame('us', $request->metadata['data_residency_region'] ?? null);
        $this->assertStringContainsString('[redacted-email]', $request->messages[0]['content']);
        $this->assertStringNotContainsString('customer@example.com', $request->messages[0]['content']);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function function(
        FakeProvider $provider,
        string $auditPath,
        ?BasicPolicyEngine $policy = null,
        int $maxRetries = 0,
        array $metadata = [],
        ?PiiRedactor $redactor = null,
    ): SmartFunctionDefinition {
        return new SmartFunctionDefinition(
            name: 'catalog.summary',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            prompt: new StringPromptTemplate('Summarize {{ title }} as JSON.'),
            validator: new JsonSchemaValidator(),
            outputSchema: self::OUTPUT_SCHEMA,
            policy: $policy ?? new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog($auditPath),
            maxRetries: $maxRetries,
            metadata: $metadata,
            redactor: $redactor,
        );
    }
}
