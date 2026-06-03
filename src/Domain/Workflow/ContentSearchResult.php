<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

final readonly class ContentSearchResult
{
    public function __construct(
        public string $id,
        public string $title,
        public string $excerpt,
    ) {
    }
}
