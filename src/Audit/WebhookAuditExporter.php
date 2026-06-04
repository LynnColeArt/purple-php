<?php

declare(strict_types=1);

namespace Purple\Audit;

use Purple\Contracts\Audit\AuditExporter;
use Purple\Contracts\Security\DataRedactor;
use Purple\Contracts\Security\SecretResolver;
use Purple\Domain\Audit\AuditExportRecord;
use RuntimeException;

final readonly class WebhookAuditExporter implements AuditExporter
{
    /** @var null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> */
    private mixed $transport;

    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private string $endpoint,
        private ?SecretResolver $secrets = null,
        private string $secretName = 'PURPLE_OBSERVABILITY_TOKEN',
        private ?DataRedactor $redactor = null,
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function export(AuditExportRecord $record): void
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->secrets !== null) {
            $headers['Authorization'] = 'Bearer ' . $this->secrets->resolve($this->secretName)->reveal();
        }

        $payload = $record->toExportPayload();

        if ($this->redactor !== null) {
            $payload = $this->redactedPayload($payload);
        }

        $transport = $this->transport ?? $this->defaultTransport(...);
        $transport('POST', $this->endpoint, $headers, $payload);
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

        if (file_get_contents($url, false, $context) === false) {
            throw new RuntimeException('Webhook audit export failed.');
        }

        return ['status' => 'sent'];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function redactedPayload(array $payload): array
    {
        $redacted = $this->redactor?->redact($payload);

        if (! is_array($redacted) || array_is_list($redacted)) {
            return $payload;
        }

        $result = [];

        foreach ($redacted as $key => $item) {
            if (! is_string($key)) {
                return $payload;
            }

            $result[$key] = $item;
        }

        return $result;
    }
}
