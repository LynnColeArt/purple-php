<?php

declare(strict_types=1);

namespace Purple\Security;

use Purple\Contracts\Security\DataRedactor;

final readonly class PiiRedactor implements DataRedactor
{
    public function redact(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->redactString($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            $redacted[$key] = $this->redact($item);
        }

        return $redacted;
    }

    private function redactString(string $value): string
    {
        $value = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $value) ?? $value;
        $value = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[redacted-ssn]', $value) ?? $value;
        $value = preg_replace('/\b(?:\d[ -]*?){13,19}\b/', '[redacted-card]', $value) ?? $value;
        $value = preg_replace('/\b(?:\+?1[ .-]?)?(?:\(?\d{3}\)?[ .-]?)\d{3}[ .-]?\d{4}\b/', '[redacted-phone]', $value) ?? $value;

        return $value;
    }
}
