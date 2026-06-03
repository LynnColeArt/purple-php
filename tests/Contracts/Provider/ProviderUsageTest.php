<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Provider;

use InvalidArgumentException;
use Purple\Contracts\Provider\ProviderUsage;
use Purple\Tests\Testing\TestCase;

final class ProviderUsageTest extends TestCase
{
    public function testCalculatesTotalTokens(): void
    {
        $usage = new ProviderUsage(inputTokens: 12, outputTokens: 8, costUsd: 0.002);

        $this->assertSame(20, $usage->totalTokens());
        $this->assertSame(0.002, $usage->costUsd);
    }

    public function testRejectsNegativeUsage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be negative');

        new ProviderUsage(inputTokens: -1, outputTokens: 0);
    }
}
