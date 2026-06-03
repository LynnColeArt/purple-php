<?php

declare(strict_types=1);

namespace Purple\Contracts\Security;

use InvalidArgumentException;

final readonly class SecretValue
{
    private function __construct(private string $value)
    {
        if ($this->value === '') {
            throw new InvalidArgumentException('Secret value must not be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function reveal(): string
    {
        return $this->value;
    }

    public function redacted(): string
    {
        if (strlen($this->value) <= 8) {
            return str_repeat('*', 8);
        }

        $visibleSuffix = substr($this->value, -4);
        $maskLength = max(8, strlen($this->value) - strlen($visibleSuffix));

        return str_repeat('*', $maskLength) . $visibleSuffix;
    }

    public function __toString(): string
    {
        return $this->redacted();
    }
}
