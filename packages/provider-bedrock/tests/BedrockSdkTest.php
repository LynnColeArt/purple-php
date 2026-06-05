<?php

declare(strict_types=1);

namespace Purple\Provider\Bedrock\Tests;

use InvalidArgumentException;
use Purple\Audit\FileAuditLog;
use Purple\Provider\Bedrock\BedrockSdk;
use Purple\ProviderProfile;
use PHPUnit\Framework\TestCase;

final class BedrockSdkTest extends TestCase
{
    private const SUMMARY_SCHEMA = <<<'JSON'
{
  "type": "object",
  "required": ["summary"],
  "properties": {
    "summary": {"type": "string"}
  }
}
JSON;

    public function testCreatesSdkThroughCoreProviderContract(): void
    {
        $captured = [];
        $sdk = BedrockSdk::create(
            profile: ProviderProfile::bedrock(model: 'anthropic.model'),
            auditLog: new FileAuditLog(sys_get_temp_dir() . '/purple-bedrock-sdk-' . bin2hex(random_bytes(4)) . '.jsonl'),
            transport: function (string $method, string $url, array $headers, array $payload) use (&$captured): array {
                $captured = compact('method', 'url', 'headers', 'payload');

                return [
                    'output' => [
                        'message' => [
                            'content' => [
                                ['text' => '{"summary":"Bedrock package SDK."}'],
                            ],
                        ],
                    ],
                    'usage' => [
                        'inputTokens' => 3,
                        'outputTokens' => 4,
                    ],
                ];
            },
            region: 'us-west-2',
        );

        $function = $sdk->smartFunction(
            name: 'catalog.summary',
            prompt: 'Summarize {{ title }}.',
            outputSchema: self::SUMMARY_SCHEMA,
        );

        $this->assertSame(['summary' => 'Bedrock package SDK.'], $function->run(['title' => 'Hat']));
        $this->assertSame('POST', $captured['method'] ?? null);
        $this->assertIsString($captured['url'] ?? null);
        $this->assertStringContainsString('bedrock-runtime.us-west-2.amazonaws.com', $captured['url']);
    }

    public function testRejectsNonBedrockProfile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bedrock SDK factory requires provider profile "bedrock"');

        BedrockSdk::create(profile: ProviderProfile::fake());
    }
}
