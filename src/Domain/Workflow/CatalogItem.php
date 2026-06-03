<?php

declare(strict_types=1);

namespace Purple\Domain\Workflow;

final readonly class CatalogItem
{
    public function __construct(
        public string $sku,
        public string $title,
        public string $status,
    ) {
    }
}
