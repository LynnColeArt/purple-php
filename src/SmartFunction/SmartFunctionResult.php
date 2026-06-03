<?php

declare(strict_types=1);

namespace Purple\SmartFunction;

use Purple\Contracts\Schema\ValidationResult;

final readonly class SmartFunctionResult
{
    public function __construct(
        public mixed $output,
        public string $rawContent,
        public ValidationResult $validation,
        public string $runId,
        public int $attempts,
    ) {
    }
}
