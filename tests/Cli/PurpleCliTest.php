<?php

declare(strict_types=1);

namespace Purple\Tests\Cli;

use Purple\Cli\PurpleCli;
use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Tests\Testing\TestCase;

final class PurpleCliTest extends TestCase
{
    public function testRunsSmartFunctionDemoAndInspectsAudit(): void
    {
        $auditPath = sys_get_temp_dir() . '/purple-cli-demo-' . bin2hex(random_bytes(4)) . '.jsonl';
        $cli = new PurpleCli();
        $demoOutput = '';

        $demoExit = $cli->run(['purple', 'demo', 'smart-function', $auditPath], static function (string $output) use (&$demoOutput): void {
            $demoOutput .= $output;
        });

        $this->assertSame(0, $demoExit);
        $demoPayload = $this->decodeObject($demoOutput);
        $demoResult = $demoPayload['output'] ?? null;

        $this->assertIsArray($demoResult);
        $this->assertSame('smart-function', $demoPayload['demo']);
        $this->assertSame($auditPath, $demoPayload['audit_path']);
        $this->assertSame('CLI demo product summary.', $demoResult['summary'] ?? null);

        $auditOutput = '';
        $auditExit = $cli->run(['purple', 'audit', 'inspect', $auditPath], static function (string $output) use (&$auditOutput): void {
            $auditOutput .= $output;
        });
        $events = $this->decodeObjectList($auditOutput);

        $this->assertSame(0, $auditExit);
        $this->assertSame('smart_function.policy_decided', $events[0]['type'] ?? null);
        $this->assertSame('smart_function.completed', $events[2]['type'] ?? null);
    }

    public function testRunsChatAndAgentDemos(): void
    {
        $cli = new PurpleCli();
        $chatAuditPath = sys_get_temp_dir() . '/purple-cli-chat-' . bin2hex(random_bytes(4)) . '.jsonl';
        $agentAuditPath = sys_get_temp_dir() . '/purple-cli-agent-' . bin2hex(random_bytes(4)) . '.jsonl';
        $chatOutput = '';
        $agentOutput = '';

        $chatExit = $cli->run(['purple', 'demo', 'chat', $chatAuditPath], static function (string $output) use (&$chatOutput): void {
            $chatOutput .= $output;
        });
        $agentExit = $cli->run(['purple', 'demo', 'agent', $agentAuditPath], static function (string $output) use (&$agentOutput): void {
            $agentOutput .= $output;
        });

        $chatPayload = $this->decodeObject($chatOutput);
        $agentPayload = $this->decodeObject($agentOutput);

        $this->assertSame(0, $chatExit);
        $this->assertSame('chat', $chatPayload['demo']);
        $this->assertSame(3, $chatPayload['message_count']);
        $this->assertIsArray($chatPayload['chunks']);
        $this->assertNotEmpty($chatPayload['chunks']);
        $this->assertSame($chatAuditPath, $chatPayload['audit_path']);

        $this->assertSame(0, $agentExit);
        $this->assertSame('agent', $agentPayload['demo']);
        $this->assertSame('completed', $agentPayload['status']);
        $this->assertSame(1, $agentPayload['tool_calls']);
        $this->assertIsArray($agentPayload['tool_log']);
        $this->assertCount(1, $agentPayload['tool_log']);
        $this->assertSame($agentAuditPath, $agentPayload['audit_path']);
    }

    public function testChecksFakeProviderAndDiagnostics(): void
    {
        $cli = new PurpleCli();
        $providerOutput = '';
        $diagnosticsOutput = '';

        $providerExit = $cli->run(['purple', 'provider', 'check', 'fake'], static function (string $output) use (&$providerOutput): void {
            $providerOutput .= $output;
        });
        $diagnosticsExit = $cli->run(['purple', 'diagnostics'], static function (string $output) use (&$diagnosticsOutput): void {
            $diagnosticsOutput .= $output;
        });

        $providerPayload = $this->decodeObject($providerOutput);
        $diagnosticsPayload = $this->decodeObject($diagnosticsOutput);

        $this->assertSame(0, $providerExit);
        $this->assertSame('fake', $providerPayload['provider']);
        $this->assertSame('fake-model', $providerPayload['model']);
        $this->assertSame('ok', $providerPayload['status']);
        $this->assertFalse($providerPayload['secret_required']);
        $this->assertSame(0, $diagnosticsExit);
        $this->assertTrue($diagnosticsPayload['composer_mode']);
        $this->assertFalse($diagnosticsPayload['native_runtime_required']);
    }

    public function testRunsSidecarResumePrototype(): void
    {
        $directory = sys_get_temp_dir() . '/purple-cli-sidecar-' . bin2hex(random_bytes(4));
        (new FileDurableRunStore($directory))->save(new DurableRunRecord('run-123', 'paused'));
        $cli = new PurpleCli();
        $output = '';

        $exit = $cli->run(['purple', 'sidecar', 'resume', $directory, 'run-123', 'cli-sidecar'], static function (string $chunk) use (&$output): void {
            $output .= $chunk;
        });
        $payload = $this->decodeObject($output);
        $metadata = $payload['metadata'] ?? null;

        $this->assertSame(0, $exit);
        $this->assertSame('sidecar-runtime-service', $payload['prototype']);
        $this->assertSame('run-123', $payload['run_id']);
        $this->assertSame('accepted', $payload['status']);
        $this->assertIsArray($metadata);
        $this->assertSame('cli-sidecar', $metadata['sidecar_node'] ?? null);
        $this->assertSame('accepted', $metadata['reason'] ?? null);
    }

    public function testSidecarResumePrototypeReportsMissingRun(): void
    {
        $directory = sys_get_temp_dir() . '/purple-cli-sidecar-' . bin2hex(random_bytes(4));
        $cli = new PurpleCli();
        $output = '';

        $exit = $cli->run(['purple', 'sidecar', 'resume', $directory, 'missing-run'], static function (string $chunk) use (&$output): void {
            $output .= $chunk;
        });
        $payload = $this->decodeObject($output);
        $metadata = $payload['metadata'] ?? null;

        $this->assertSame(1, $exit);
        $this->assertSame('missing-run', $payload['run_id']);
        $this->assertSame('rejected', $payload['status']);
        $this->assertIsArray($metadata);
        $this->assertSame('missing_run', $metadata['reason'] ?? null);
    }

    public function testSidecarResumePrototypeReportsUsageErrors(): void
    {
        $cli = new PurpleCli();
        $output = '';

        $exit = $cli->run(['purple', 'sidecar', 'resume'], static function (string $chunk) use (&$output): void {
            $output .= $chunk;
        });

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Usage: purple sidecar resume', $output);
    }

    public function testOpenAIProviderCheckReportsMissingSecret(): void
    {
        putenv('PURPLE_CLI_MISSING_OPENAI_KEY');
        $cli = new PurpleCli();
        $output = '';

        $exit = $cli->run(['purple', 'provider', 'check', 'openai', 'PURPLE_CLI_MISSING_OPENAI_KEY'], static function (string $chunk) use (&$output): void {
            $output .= $chunk;
        });
        $payload = $this->decodeObject($output);

        $this->assertSame(1, $exit);
        $this->assertSame('openai', $payload['provider']);
        $this->assertSame('missing_secret', $payload['status']);
        $this->assertTrue($payload['secret_required']);
        $this->assertSame('PURPLE_CLI_MISSING_OPENAI_KEY', $payload['secret_name']);
        $this->assertFalse($payload['secret_configured']);
    }

    public function testOpenAIProviderCheckDoesNotExposeConfiguredSecret(): void
    {
        putenv('PURPLE_CLI_OPENAI_KEY=sk-purple-cli-secret');
        $cli = new PurpleCli();
        $output = '';

        try {
            $exit = $cli->run(['purple', 'provider', 'check', 'openai', 'PURPLE_CLI_OPENAI_KEY'], static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            });
            $payload = $this->decodeObject($output);

            $this->assertSame(0, $exit);
            $this->assertSame('openai', $payload['provider']);
            $this->assertSame('ok', $payload['status']);
            $this->assertTrue($payload['secret_required']);
            $this->assertSame('PURPLE_CLI_OPENAI_KEY', $payload['secret_name']);
            $this->assertTrue($payload['secret_configured']);
            $this->assertStringNotContainsString('sk-purple-cli-secret', $output);
        } finally {
            putenv('PURPLE_CLI_OPENAI_KEY');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            $this->fail('Expected a JSON object.');
        }

        $object = [];

        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                $this->fail('Expected JSON object keys to be strings.');
            }

            $object[$key] = $value;
        }

        return $object;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeObjectList(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            $this->fail('Expected a JSON array.');
        }

        $objects = [];

        foreach ($decoded as $item) {
            if (! is_array($item) || array_is_list($item)) {
                $this->fail('Expected every JSON array item to be an object.');
            }

            $object = [];

            foreach ($item as $key => $value) {
                if (! is_string($key)) {
                    $this->fail('Expected JSON object keys to be strings.');
                }

                $object[$key] = $value;
            }

            $objects[] = $object;
        }

        return $objects;
    }
}
