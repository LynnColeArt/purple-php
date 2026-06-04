<?php

declare(strict_types=1);

namespace Purple\Tests\Schema;

use Purple\Schema\JsonSchemaValidator;
use Purple\Tests\Testing\TestCase;

final class JsonSchemaValidatorTest extends TestCase
{
    private const SCHEMA = <<<'JSON'
{
  "type": "object",
  "required": ["title", "score"],
  "properties": {
    "title": {"type": "string"},
    "score": {"type": "number"}
  }
}
JSON;

    public function testValidatesStructuredOutput(): void
    {
        $result = (new JsonSchemaValidator())->validate([
            'title' => 'Catalog summary',
            'score' => 0.92,
        ], self::SCHEMA);

        $this->assertTrue($result->valid);
    }

    public function testAcceptsEmptyObjectSchema(): void
    {
        $result = (new JsonSchemaValidator())->validate(['anything' => true], '{}');

        $this->assertTrue($result->valid);
    }

    public function testReportsMissingAndInvalidProperties(): void
    {
        $result = (new JsonSchemaValidator())->validate([
            'title' => 42,
        ], self::SCHEMA);

        $this->assertFalse($result->valid);
        $this->assertSame([
            '$.score is required.',
            '$.title must be string.',
        ], $result->violations);
    }
}
