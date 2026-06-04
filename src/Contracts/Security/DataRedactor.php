<?php

declare(strict_types=1);

namespace Purple\Contracts\Security;

interface DataRedactor
{
    public function redact(mixed $value): mixed;
}
