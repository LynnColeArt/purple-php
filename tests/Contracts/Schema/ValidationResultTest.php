<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Schema;

use Purple\Contracts\Schema\ValidationResult;
use Purple\Tests\Testing\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testPassResultHasNoViolations(): void
    {
        $result = ValidationResult::pass();

        $this->assertTrue($result->valid);
        $this->assertSame([], $result->violations);
    }

    public function testFailResultCarriesViolations(): void
    {
        $result = ValidationResult::fail(['missing field: title']);

        $this->assertFalse($result->valid);
        $this->assertSame(['missing field: title'], $result->violations);
    }
}
