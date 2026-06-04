<?php

declare(strict_types=1);

namespace Purple\Cli;

use Purple\Agent\AgentLimits;
use Purple\Agent\AgentRunner;
use Purple\Agent\AgentTool;
use Purple\Agent\AgentToolCallRecord;
use Purple\Agent\AgentToolRegistry;
use Purple\Audit\FileAuditLog;
use Purple\Chat\ChatHistory;
use Purple\Chat\ChatMessage;
use Purple\Chat\ChatResponseChunk;
use Purple\Chat\ChatSession;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\ProviderProfile;
use Purple\Policy\BasicPolicyEngine;
use Purple\Prompt\StringPromptTemplate;
use Purple\Schema\JsonSchemaValidator;
use Purple\Security\EnvironmentSecretResolver;
use Purple\SmartFunction\SmartFunctionDefinition;
use Purple\Testing\FakeProvider;
use Purple\Tool\ToolDefinition;
use Purple\Tool\ToolSideEffectLevel;
use InvalidArgumentException;
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
            '  demo chat [audit-path]',
            '  demo agent [audit-path]',
            '  audit inspect <audit-path>',
            '  provider check fake',
            '  provider check openai [secret-name]',
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
        return match ($args[0] ?? '') {
            'smart-function' => $this->demoSmartFunction($args[1] ?? null, $write),
            'chat' => $this->demoChat($args[1] ?? null, $write),
            'agent' => $this->demoAgent($args[1] ?? null, $write),
            default => $this->error('Usage: purple demo <smart-function|chat|agent> [audit-path]', $write),
        };
    }

    /**
     * @param callable(string): void $write
     */
    private function demoSmartFunction(?string $auditPath, callable $write): int
    {
        $auditPath ??= sys_get_temp_dir() . '/purple-cli-smart-function-demo.jsonl';
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
     * @param callable(string): void $write
     */
    private function demoChat(?string $auditPath, callable $write): int
    {
        $auditPath ??= sys_get_temp_dir() . '/purple-cli-chat-demo.jsonl';
        $session = new ChatSession(
            name: 'support.chat',
            providerName: 'fake',
            model: 'fake-model',
            provider: FakeProvider::replying('Try clearing the cart and re-applying the discount code.'),
            policy: new BasicPolicyEngine(allowedProviders: ['fake'], allowedModels: ['fake-model']),
            auditLog: new FileAuditLog($auditPath),
            history: new ChatHistory([
                ChatMessage::system('Answer as an ecommerce support assistant.'),
            ]),
        );
        $response = $session->send('The discount code fails at checkout.');

        $this->writeJson([
            'demo' => 'chat',
            'assistant' => $response->content,
            'message_count' => $response->history->count(),
            'chunks' => array_map(
                static fn (ChatResponseChunk $chunk): array => [
                    'index' => $chunk->index,
                    'content' => $chunk->content,
                    'final' => $chunk->final,
                ],
                iterator_to_array($response->chunks(24)),
            ),
            'audit_path' => $auditPath,
        ], $write);

        return 0;
    }

    /**
     * @param callable(string): void $write
     */
    private function demoAgent(?string $auditPath, callable $write): int
    {
        $auditPath ??= sys_get_temp_dir() . '/purple-cli-agent-demo.jsonl';
        $provider = new FakeProvider([
            new ProviderResponse('{"action":"tool","tool":"catalog.lookup","input":{"sku":"SKU-1"}}'),
            new ProviderResponse('{"action":"complete","answer":"SKU-1 is ready for the catalog brief."}'),
        ]);
        $tools = new AgentToolRegistry([
            new AgentTool(
                new ToolDefinition(
                    name: 'catalog.lookup',
                    description: 'Look up catalog metadata for a SKU.',
                    inputSchema: '{"type":"object","required":["sku"],"properties":{"sku":{"type":"string"}}}',
                    outputSchema: '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}}}',
                    sideEffectLevel: ToolSideEffectLevel::Read,
                    maxRetries: 1,
                ),
                static fn (array $input): array => [
                    'sku' => $input['sku'] ?? '',
                    'title' => 'Merino travel cardigan',
                ],
            ),
        ]);
        $runner = new AgentRunner(
            name: 'catalog.agent',
            providerName: 'fake',
            model: 'fake-model',
            provider: $provider,
            policy: new BasicPolicyEngine(allowedProviders: ['fake', 'catalog.lookup'], allowedModels: ['fake-model', 'read']),
            auditLog: new FileAuditLog($auditPath),
            tools: $tools,
            limits: new AgentLimits(maxSteps: 3),
        );
        $result = $runner->run('Prepare a catalog brief for SKU-1.');

        $this->writeJson([
            'demo' => 'agent',
            'status' => $result->status->value,
            'answer' => $result->answer,
            'steps' => $result->steps,
            'tool_calls' => $result->toolCalls,
            'tool_log' => array_map(
                static fn (AgentToolCallRecord $record): array => $record->toArray(),
                $result->toolLog,
            ),
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
        if (($args[0] ?? '') !== 'check' || ! isset($args[1])) {
            return $this->error('Usage: purple provider check <fake|openai> [secret-name]', $write);
        }

        return match ($args[1]) {
            'fake' => $this->checkFakeProvider($write),
            'openai' => $this->checkOpenAIProvider($args[2] ?? 'OPENAI_API_KEY', $write),
            default => $this->error('Usage: purple provider check <fake|openai> [secret-name]', $write),
        };
    }

    /**
     * @param callable(string): void $write
     */
    private function checkFakeProvider(callable $write): int
    {
        $profile = ProviderProfile::fake();

        $this->writeJson([
            'provider' => $profile->providerName,
            'model' => $profile->model,
            'status' => 'ok',
            'secret_required' => false,
        ], $write);

        return 0;
    }

    /**
     * @param callable(string): void $write
     */
    private function checkOpenAIProvider(string $secretName, callable $write): int
    {
        $profile = ProviderProfile::openAI(secretName: $secretName);

        try {
            (new EnvironmentSecretResolver())->resolve($secretName);
        } catch (InvalidArgumentException $exception) {
            $this->writeJson([
                'provider' => $profile->providerName,
                'model' => $profile->model,
                'status' => 'missing_secret',
                'secret_required' => true,
                'secret_name' => $secretName,
                'secret_configured' => false,
                'message' => $exception->getMessage(),
            ], $write);

            return 1;
        }

        $this->writeJson([
            'provider' => $profile->providerName,
            'model' => $profile->model,
            'status' => 'ok',
            'secret_required' => true,
            'secret_name' => $secretName,
            'secret_configured' => true,
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
