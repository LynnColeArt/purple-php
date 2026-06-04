<?php

declare(strict_types=1);

namespace Purple\Security;

use JsonException;
use Purple\Contracts\Security\SecretResolver;
use Purple\Contracts\Security\SecretValue;
use RuntimeException;

final readonly class VaultSecretResolver implements SecretResolver
{
    /** @var null|callable(string, string, array<string, string>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param null|callable(string, string, array<string, string>): array<string, mixed> $transport
     */
    public function __construct(
        private string $endpoint,
        private SecretValue $token,
        private string $mount = 'secret',
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function resolve(string $name): SecretValue
    {
        if (trim($name) === '') {
            throw new RuntimeException('Vault secret name must not be empty.');
        }

        $transport = $this->transport ?? $this->defaultTransport(...);
        $payload = $transport('GET', $this->url($name), [
            'X-Vault-Token' => $this->token->reveal(),
            'Accept' => 'application/json',
        ]);
        $value = $this->secretValueFromPayload($payload);

        if ($value === null || $value === '') {
            throw new RuntimeException(sprintf('Vault secret "%s" did not include a value.', $name));
        }

        return SecretValue::fromString($value);
    }

    private function url(string $name): string
    {
        return sprintf(
            '%s/v1/%s/data/%s',
            rtrim($this->endpoint, '/'),
            rawurlencode($this->mount),
            str_replace('%2F', '/', rawurlencode($name)),
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    private function defaultTransport(string $method, string $url, array $headers): array
    {
        $headerLines = [];

        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\n", $headerLines),
                'ignore_errors' => true,
            ],
        ]);
        $raw = file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new RuntimeException('Vault secret request failed.');
        }

        return $this->decodeJsonObject($raw, 'Vault response');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function secretValueFromPayload(array $payload): ?string
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data) || array_is_list($data)) {
            return null;
        }

        $inner = $data['data'] ?? $data;

        if (! is_array($inner) || array_is_list($inner)) {
            return null;
        }

        $value = $inner['value'] ?? $inner['secret'] ?? null;

        return is_string($value) ? $value : null;
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
