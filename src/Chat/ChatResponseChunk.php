<?php

declare(strict_types=1);

namespace Purple\Chat;

final readonly class ChatResponseChunk
{
    public function __construct(
        public int $index,
        public string $content,
        public bool $final = false,
    ) {
    }
}
