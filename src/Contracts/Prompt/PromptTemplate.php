<?php

declare(strict_types=1);

namespace Purple\Contracts\Prompt;

interface PromptTemplate
{
    /**
     * @param array<string, mixed> $input
     */
    public function render(array $input): string;
}
