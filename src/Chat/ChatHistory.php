<?php

declare(strict_types=1);

namespace Purple\Chat;

final class ChatHistory
{
    /** @var list<ChatMessage> */
    private array $messages = [];

    /**
     * @param list<ChatMessage> $messages
     */
    public function __construct(array $messages = [])
    {
        foreach ($messages as $message) {
            $this->add($message);
        }
    }

    public function add(ChatMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return list<ChatMessage>
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * @return non-empty-list<array{role: non-empty-string, content: non-empty-string}>
     */
    public function toProviderMessages(): array
    {
        $messages = array_map(
            static fn (ChatMessage $message): array => $message->toProviderMessage(),
            $this->messages,
        );

        if ($messages === []) {
            return [
                [
                    'role' => 'user',
                    'content' => '(empty chat history)',
                ],
            ];
        }

        return $messages;
    }

    public function count(): int
    {
        return count($this->messages);
    }
}
