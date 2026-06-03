<?php

declare(strict_types=1);

namespace Purple\Contracts\Schema;

interface SchemaValidator
{
    public function validate(mixed $value, string $schema): ValidationResult;
}
