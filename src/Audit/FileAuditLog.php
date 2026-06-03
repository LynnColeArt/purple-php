<?php

declare(strict_types=1);

namespace Purple\Audit;

use JsonException;
use Purple\Contracts\Audit\AuditEvent;
use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Security\SecretValue;
use RuntimeException;

final readonly class FileAuditLog implements AuditLog
{
    public function __construct(private string $path)
    {
    }

    public function record(AuditEvent $event): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create audit directory "%s".', $directory));
        }

        $payload = [
            'type' => $event->type,
            'run_id' => $event->runId,
            'occurred_at' => $event->occurredAt->format(DATE_ATOM),
            'metadata' => $this->redact($event->metadata),
        ];

        try {
            $line = json_encode($payload, JSON_THROW_ON_ERROR) . PHP_EOL;
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode audit event: ' . $exception->getMessage(), 0, $exception);
        }

        if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write audit log "%s".', $this->path));
        }
    }

    private function redact(mixed $value): mixed
    {
        if ($value instanceof SecretValue) {
            return $value->redacted();
        }

        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/(api[_-]?key|secret|token|password|credential)/i', $key) === 1) {
                $redacted[$key] = '[redacted]';
                continue;
            }

            $redacted[$key] = $this->redact($item);
        }

        return $redacted;
    }
}
