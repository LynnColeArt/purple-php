<?php

declare(strict_types=1);

namespace Purple\Security;

use JsonException;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use RuntimeException;

final readonly class CloudSecretResolver implements SecretResolver
{
    /** @var null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param array<string, string> $headers
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private string $endpoint,
        private string $providerName,
        private array $headers = [],
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function resolve(string $name): SecretValue
    {
        if (trim($name) === '') {
            throw new RuntimeException('Cloud secret name must not be empty.');
        }

        $transport = $this->transport ?? $this->defaultTransport(...);
        $payload = $transport('POST', rtrim($this->endpoint, '/') . '/secrets/resolve', [
            'Content-Type' => 'application/json',
            ...$this->headers,
        ], [
            'provider' => $this->providerName,
            'name' => $name,
        ]);
        $value = $payload['value'] ?? $payload['secret_string'] ?? null;

        if (! is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('Cloud secret "%s" did not include a value.', $name));
        }

        return SecretValue::fromString($value);
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

        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
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
            throw new RuntimeException('Cloud secret request failed.');
        }

        return $this->decodeJsonObject($raw, 'Cloud secret response');
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
}
