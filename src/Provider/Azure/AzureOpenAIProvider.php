<?php

declare(strict_types=1);

namespace Purple\Provider\Azure;

use JsonException;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Provider\ProviderUsage;
use Purple\Contracts\Security\SecretResolver;
use RuntimeException;

final readonly class AzureOpenAIProvider implements Provider
{
    /** @var null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private SecretResolver $secrets,
        private string $resource,
        private string $deployment,
        private string $secretName = 'AZURE_OPENAI_API_KEY',
        private string $apiVersion = '2024-02-15-preview',
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function complete(ProviderRequest $request): ProviderResponse
    {
        $secret = $this->secrets->resolve($this->secretName);
        $transport = $this->transport ?? $this->defaultTransport(...);
        $response = $transport('POST', $this->endpoint(), [
            'api-key' => $secret->reveal(),
            'Content-Type' => 'application/json',
        ], [
            'messages' => $request->messages,
            'metadata' => $request->metadata,
        ]);

        return $this->providerResponseFromPayload($response);
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://%s.openai.azure.com/openai/deployments/%s/chat/completions?api-version=%s',
            rawurlencode($this->resource),
            rawurlencode($this->deployment),
            rawurlencode($this->apiVersion),
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
        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\n", $headerLines),
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
            ],
        ]);
        $raw = file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new RuntimeException('Azure OpenAI request failed.');
        }

        return $this->decodeJsonObject($raw, 'Azure OpenAI response');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function providerResponseFromPayload(array $payload): ProviderResponse
    {
        $firstChoice = $this->arrayValue($payload, 'choices')[0] ?? null;

        if (! is_array($firstChoice)) {
            throw new RuntimeException('Azure OpenAI response did not include a choice.');
        }

        $message = $firstChoice['message'] ?? null;

        if (! is_array($message) || ! is_string($message['content'] ?? null)) {
            throw new RuntimeException('Azure OpenAI response did not include message content.');
        }

        $usage = null;
        $usagePayload = $payload['usage'] ?? null;

        if (is_array($usagePayload) && ! array_is_list($usagePayload)) {
            /** @var array<string, mixed> $usagePayload */
            $usage = new ProviderUsage(
                inputTokens: $this->intValue($usagePayload, 'prompt_tokens'),
                outputTokens: $this->intValue($usagePayload, 'completion_tokens'),
            );
        }

        return new ProviderResponse($message['content'], [
            'provider' => 'azure_openai',
            'resource' => $this->resource,
            'deployment' => $this->deployment,
        ], $usage);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $raw, string $label): array
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException($label . ' was not valid JSON.', 0, $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException($label . ' must be a JSON object.');
        }

        $result = [];

        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                throw new RuntimeException($label . ' must be a JSON object.');
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<mixed>
     */
    private function arrayValue(array $payload, string $key): array
    {
        $value = $payload[$key] ?? [];

        return is_array($value) ? $value : [];
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
