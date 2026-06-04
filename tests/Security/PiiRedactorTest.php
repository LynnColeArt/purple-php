<?php

declare(strict_types=1);

namespace Purple\Tests\Security;

use Purple\Security\PiiRedactor;
use Purple\Tests\Testing\TestCase;

final class PiiRedactorTest extends TestCase
{
    public function testRedactsCommonPiiInNestedPayloads(): void
    {
        $payload = [
            'email' => 'customer@example.com',
            'note' => 'Call 312-555-0199 about card 4242 4242 4242 4242.',
            'nested' => [
                'ssn' => '123-45-6789',
            ],
        ];

        $redacted = (new PiiRedactor())->redact($payload);

        $this->assertSame([
            'email' => '[redacted-email]',
            'note' => 'Call [redacted-phone] about card [redacted-card].',
            'nested' => [
                'ssn' => '[redacted-ssn]',
            ],
        ], $redacted);
    }
}
