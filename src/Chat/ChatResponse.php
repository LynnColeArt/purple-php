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
    public function chunks(): iterable
    {
        yield new ChatResponseChunk(0, $this->content, true);
    }
}
