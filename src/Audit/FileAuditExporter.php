<?php

declare(strict_types=1);

namespace Purple\Audit;

use JsonException;
use Purple\Contracts\Audit\AuditExporter;
use Purple\Contracts\Security\DataRedactor;
use Purple\Domain\Audit\AuditExportRecord;
use RuntimeException;

final readonly class FileAuditExporter implements AuditExporter
{
    public function __construct(
        private string $path,
        private ?DataRedactor $redactor = null,
    ) {
    }

    public function export(AuditExportRecord $record): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create audit export directory "%s".', $directory));
        }

        $payload = $record->toExportPayload();

        if ($this->redactor !== null) {
            $payload = $this->redactedPayload($payload);
        }

        try {
            $line = json_encode($payload, JSON_THROW_ON_ERROR) . PHP_EOL;
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode audit export record: ' . $exception->getMessage(), 0, $exception);
        }

        if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write audit export "%s".', $this->path));
        }
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
