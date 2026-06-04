<?php

declare(strict_types=1);

namespace Purple\Chat;

use Purple\Contracts\Provider\ProviderResponse;
use Purple\Contracts\Provider\ProviderUsage;

final readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public ChatHistory $history,
        public ProviderResponse $providerResponse,
        public string $runId,
    ) {
    }

    public function usage(): ?ProviderUsage
    {
        return $this->providerResponse->usage;
    }

    /**
     * @return iterable<ChatResponseChunk>
     */
    public function chunks(?int $chunkSize = null): iterable
    {
        if ($chunkSize !== null && $chunkSize < 1) {
            throw new ChatException('Chat response chunk size must be at least 1.');
        }

        $chunkSize ??= max(1, strlen($this->content));
        $length = strlen($this->content);

        if ($length === 0) {
            yield new ChatResponseChunk(0, '', true);

            return;
        }

        $index = 0;

        for ($offset = 0; $offset < $length; $offset += $chunkSize) {
            yield new ChatResponseChunk(
                $index,
                substr($this->content, $offset, $chunkSize),
                $offset + $chunkSize >= $length,
            );
            $index++;
        }
    }
}
