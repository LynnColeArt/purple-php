<?php

declare(strict_types=1);

namespace Purple\Tests\Cli;

use Purple\Cli\PurpleCli;
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
        $this->assertSame('ok', $providerPayload['status']);
        $this->assertSame(0, $diagnosticsExit);
        $this->assertTrue($diagnosticsPayload['composer_mode']);
        $this->assertFalse($diagnosticsPayload['native_runtime_required']);
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
