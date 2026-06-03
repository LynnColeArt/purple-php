<?php

declare(strict_types=1);

namespace Purple\Prompt;

use Purple\Contracts\Prompt\PromptTemplate;

final readonly class StringPromptTemplate implements PromptTemplate
{
    public function __construct(private string $template)
    {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function render(array $input): string
    {
        $rendered = $this->template;

        foreach ($input as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replacement = (string) $value;
            } else {
                $replacement = json_encode($value, JSON_THROW_ON_ERROR);
            }

            $rendered = str_replace('{{ ' . $key . ' }}', $replacement, $rendered);
            $rendered = str_replace('{{' . $key . '}}', $replacement, $rendered);
        }

        return $rendered;
    }
}
