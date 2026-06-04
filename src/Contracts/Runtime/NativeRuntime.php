<?php

declare(strict_types=1);

namespace Purple\Contracts\Runtime;

use Purple\Runtime\NativeRuntimeResult;

interface NativeRuntime
{
    /**
     * @param array<string, mixed> $payload
     */
    public function invoke(string $operation, array $payload): NativeRuntimeResult;
}
