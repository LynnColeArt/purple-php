<?php

declare(strict_types=1);

namespace Purple\Provider\Sidecar;

use JsonException;
use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Provider\ProviderUsage;
use Purple\Contracts\Security\SecretResolver;
use RuntimeException;

final readonly class SidecarProvider implements Provider
{
    /** @var null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private string $endpoint,
        private ?SecretResolver $secrets = null,
        private string $secretName = 'PURPLE_SIDECAR_TOKEN',
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function complete(ProviderRequest $request): ProviderResponse
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->secrets !== null) {
            $headers['Authorization'] = 'Bearer ' . $this->secrets->resolve($this->secretName)->reveal();
        }

        $transport = $this->transport ?? $this->defaultTransport(...);
        $response = $transport('POST', rtrim($this->endpoint, '/') . '/v1/provider/complete', $headers, [
            'model' => $request->model,
            'messages' => $request->messages,
            'metadata' => $request->metadata,
        ]);

        return $this->providerResponseFromPayload($response);
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
            throw new RuntimeException('Sidecar provider request failed.');
        }

        return $this->decodeJsonObject($raw, 'Sidecar response');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function providerResponseFromPayload(array $payload): ProviderResponse
    {
        $content = $payload['content'] ?? null;

        if (! is_string($content)) {
            throw new RuntimeException('Sidecar response did not include content.');
        }

        $metadata = $payload['metadata'] ?? [];

        if (! is_array($metadata) || array_is_list($metadata)) {
            $metadata = [];
        }

        /** @var array<string, mixed> $metadata */
        $metadata = [
            'provider' => 'sidecar',
            ...$metadata,
        ];
        $usage = null;
        $usagePayload = $payload['usage'] ?? null;

        if (is_array($usagePayload) && ! array_is_list($usagePayload)) {
            /** @var array<string, mixed> $usagePayload */
            $usage = new ProviderUsage(
                inputTokens: $this->intValue($usagePayload, 'input_tokens'),
                outputTokens: $this->intValue($usagePayload, 'output_tokens'),
                costUsd: $this->floatValue($usagePayload, 'cost_usd'),
            );
        }

        return new ProviderResponse($content, $metadata, $usage);
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
     */
    private function intValue(array $payload, string $key): int
    {
        $value = $payload[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function floatValue(array $payload, string $key): ?float
    {
        $value = $payload[$key] ?? null;

        return is_float($value) || is_int($value) ? (float) $value : null;
    }
}
