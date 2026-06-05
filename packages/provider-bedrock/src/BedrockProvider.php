<?php

declare(strict_types=1);

namespace Purple\Provider\Bedrock;

use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Provider\ProviderUsage;
use RuntimeException;

final readonly class BedrockProvider implements Provider
{
    /** @var null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private string $region = 'us-east-1',
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function complete(ProviderRequest $request): ProviderResponse
    {
        $transport = $this->transport ?? $this->defaultTransport(...);
        $response = $transport('POST', $this->endpoint($request->model), [
            'Content-Type' => 'application/json',
        ], [
            'messages' => array_map(
                static fn (array $message): array => [
                    'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => [
                        ['text' => $message['content']],
                    ],
                ],
                $request->messages,
            ),
            'metadata' => $request->metadata,
        ]);

        return $this->providerResponseFromPayload($response);
    }

    private function endpoint(string $model): string
    {
        return sprintf(
            'https://bedrock-runtime.%s.amazonaws.com/model/%s/converse',
            rawurlencode($this->region),
            rawurlencode($model),
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function defaultTransport(string $method, string $url, array $headers, array $payload): array
    {
        throw new RuntimeException(sprintf(
            'Bedrock provider requires a signed transport for %s %s.',
            $method,
            $url,
        ));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function providerResponseFromPayload(array $payload): ProviderResponse
    {
        $output = $payload['output'] ?? null;
        $message = is_array($output) ? ($output['message'] ?? null) : null;
        $content = is_array($message) ? ($message['content'] ?? null) : null;
        $firstContent = is_array($content) ? ($content[0] ?? null) : null;
        $text = is_array($firstContent) ? ($firstContent['text'] ?? null) : null;

        if (! is_string($text)) {
            throw new RuntimeException('Bedrock response did not include output message text.');
        }

        $usage = null;
        $usagePayload = $payload['usage'] ?? null;

        if (is_array($usagePayload) && ! array_is_list($usagePayload)) {
            /** @var array<string, mixed> $usagePayload */
            $usage = new ProviderUsage(
                inputTokens: $this->intValue($usagePayload, 'inputTokens'),
                outputTokens: $this->intValue($usagePayload, 'outputTokens'),
            );
        }

        return new ProviderResponse($text, [
            'provider' => 'bedrock',
            'region' => $this->region,
        ], $usage);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function intValue(array $payload, string $key): int
    {
        $value = $payload[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }
}
