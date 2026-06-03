<?php

declare(strict_types=1);

namespace Purple\Cli;

use Purple\Audit\FileAuditLog;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;
use Throwable;

final readonly class PurpleCli
{
    /**
     * @param list<string> $argv
     * @param null|callable(string): void $write
     */
    public function run(array $argv, ?callable $write = null): int
    {
        $writer = $write ?? static function (string $output): void {
            fwrite(STDOUT, $output);
        };
        $args = $this->normalizeArgs($argv);
        $command = $args[0] ?? 'help';

        try {
            return match ($command) {
                'help', '--help', '-h' => $this->help($writer),
                'demo' => $this->demo(array_slice($args, 1), $writer),
                'audit' => $this->audit(array_slice($args, 1), $writer),
                'provider' => $this->provider(array_slice($args, 1), $writer),
                'diagnostics' => $this->diagnostics($writer),
                default => $this->error(sprintf('Unknown command "%s".', $command), $writer),
            };
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage(), $writer);
        }
    }

    /**
     * @param callable(string): void $write
     */
    private function help(callable $write): int
    {
        $write(implode(PHP_EOL, [
            'Purple PHP CLI',
            'Commands:',
            '  demo smart-function [audit-path]',
            '  audit inspect <audit-path>',
            '  provider check fake',
            '  diagnostics',
            '',
        ]));

        return 0;
    }

    /**
     * @param list<string> $args
     * @param callable(string): void $write
     */
    private function demo(array $args, callable $write): int
    {
        if (($args[0] ?? '') !== 'smart-function') {
            return $this->error('Usage: purple demo smart-function [audit-path]', $write);
        }

        $auditPath = $args[1] ?? sys_get_temp_dir() . '/purple-cli-smart-function-demo.jsonl';
        $function = new SmartFunctionDefinition(
            name: 'catalog.summary',
            providerName: 'fake',
            model: 'fake-model',
            provider: FakeProvider::replying('{"summary":"CLI demo product summary."}'),
            prompt: new StringPromptTemplate('Summarize {{ title }} for a product catalog as JSON.'),
            validator: new JsonSchemaValidator(),
            outputSchema: '{"type":"object","required":["summary"],"properties":{"summary":{"type":"string"}}}',
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog($auditPath),
        );

        $output = $function->run(['title' => 'Merino travel cardigan']);
        $this->writeJson([
            'demo' => 'smart-function',
            'output' => $output,
            'audit_path' => $auditPath,
        ], $write);

        return 0;
    }

    /**
     * @param list<string> $args
     * @param callable(string): void $write
     */
    private function audit(array $args, callable $write): int
    {
        if (($args[0] ?? '') !== 'inspect' || ! isset($args[1])) {
            return $this->error('Usage: purple audit inspect <audit-path>', $write);
        }

        $this->writeJson((new AuditInspector())->inspect($args[1]), $write);

        return 0;
    }

    /**
     * @param list<string> $args
     * @param callable(string): void $write
     */
    private function provider(array $args, callable $write): int
    {
        if (($args[0] ?? '') !== 'check' || ($args[1] ?? '') !== 'fake') {
            return $this->error('Usage: purple provider check fake', $write);
        }

        $this->writeJson([
            'provider' => 'fake',
            'status' => 'ok',
        ], $write);

        return 0;
    }

    /**
     * @param callable(string): void $write
     */
    private function diagnostics(callable $write): int
    {
        $this->writeJson([
            'php_version' => PHP_VERSION,
            'composer_mode' => true,
            'native_runtime_required' => false,
        ], $write);

        return 0;
    }

    /**
     * @param callable(string): void $write
     */
    private function error(string $message, callable $write): int
    {
        $write('Error: ' . $message . PHP_EOL);

        return 1;
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $payload
     * @param callable(string): void $write
     */
    private function writeJson(array $payload, callable $write): void
    {
        $write(json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);
    }

    /**
     * @param list<string> $argv
     *
     * @return list<string>
     */
    private function normalizeArgs(array $argv): array
    {
        $args = $argv;

        if ($args !== [] && str_contains($args[0], 'purple')) {
            array_shift($args);
        }

        return $args;
    }
}
