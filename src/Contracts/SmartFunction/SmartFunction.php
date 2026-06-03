<?php

declare(strict_types=1);

namespace Purple\Contracts\SmartFunction;

interface SmartFunction
{
    public function name(): string;

    public function run(mixed $input): mixed;
}
