<?php

declare(strict_types=1);

namespace Purple\Contracts\Provider;

use InvalidArgumentException;

final readonly class ProviderRequest
{
    /**
     * @var non-empty-list<array{role: non-empty-string, content: non-empty-string}>
     */
    public array $messages;

    /**
     * @param list<array{role?: mixed, content?: mixed}> $messages
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $model,
        array $messages,
        public array $metadata = [],
    ) {
        if (trim($this->model) === '') {
            throw new InvalidArgumentException('Provider request model must not be empty.');
        }

        $this->messages = $this->normalizeMessages($messages);
    }

    /**
     * @param list<array{role?: mixed, content?: mixed}> $messages
     *
     * @return non-empty-list<array{role: non-empty-string, content: non-empty-string}>
     */
    private function normalizeMessages(array $messages): array
    {
        if ($messages === []) {
            throw new InvalidArgumentException('Provider request must include at least one message.');
        }

        $normalized = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? null;
            $content = $message['content'] ?? null;

            if (! is_string($role) || ! is_string($content)) {
                throw new InvalidArgumentException('Provider messages must include role and content.');
            }

            $role = trim($role);

            if ($role === '' || $content === '' || trim($content) === '') {
                throw new InvalidArgumentException('Provider message role and content must not be empty.');
            }

            $normalized[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function fromPrompt(string $model, string $prompt, array $metadata = []): self
    {
        return new self($model, [
            ['role' => 'user', 'content' => $prompt],
        ], $metadata);
    }
}
