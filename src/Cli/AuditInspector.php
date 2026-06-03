<?php

declare(strict_types=1);

namespace Purple\Cli;

use InvalidArgumentException;
use JsonException;

final class AuditInspector
{
    /**
     * @return list<array<string, mixed>>
     */
    public function inspect(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException(sprintf('Audit log "%s" is not readable.', $path));
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new InvalidArgumentException(sprintf('Audit log "%s" could not be read.', $path));
        }

        $events = [];

        foreach ($lines as $index => $line) {
            try {
                $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException(sprintf('Audit log line %d is not valid JSON.', $index + 1), 0, $exception);
            }

            if (! is_array($decoded) || array_is_list($decoded)) {
                throw new InvalidArgumentException(sprintf('Audit log line %d must be a JSON object.', $index + 1));
            }

            /** @var array<string, mixed> $decoded */
            $events[] = $decoded;
        }

        return $events;
    }
}
