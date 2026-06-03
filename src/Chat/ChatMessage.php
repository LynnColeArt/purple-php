<?php

declare(strict_types=1);

namespace Purple\Chat;

use InvalidArgumentException;

final readonly class ChatMessage
{
    private const ROLES = ['system', 'user', 'assistant', 'tool'];

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $role,
        public string $content,
        public array $metadata = [],
    ) {
        if (! in_array($this->role, self::ROLES, true)) {
            throw new InvalidArgumentException(sprintf('Chat role "%s" is not supported.', $this->role));
        }

        if (trim($this->content) === '') {
            throw new InvalidArgumentException('Chat message content must not be empty.');
        }
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /**
     * @return array{role: non-empty-string, content: non-empty-string}
     */
    public function toProviderMessage(): array
    {
        /** @var non-empty-string $role */
        $role = $this->role;
        /** @var non-empty-string $content */
        $content = $this->content;

        return [
            'role' => $role,
            'content' => $content,
        ];
    }
}
