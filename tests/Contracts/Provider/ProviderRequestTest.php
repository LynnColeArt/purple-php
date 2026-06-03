<?php

declare(strict_types=1);

namespace Purple\Tests\Contracts\Provider;

use InvalidArgumentException;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Tests\Testing\TestCase;

final class ProviderRequestTest extends TestCase
{
    public function testCreatesRequestFromPrompt(): void
    {
        $request = ProviderRequest::fromPrompt('test-model', 'Summarize this.', [
            'run_id' => 'run-123',
        ]);

        $this->assertSame('test-model', $request->model);
        $this->assertSame([
            ['role' => 'user', 'content' => 'Summarize this.'],
        ], $request->messages);
        $this->assertSame('run-123', $request->metadata['run_id']);
    }

    public function testPreservesPromptWhitespaceAfterValidation(): void
    {
        $request = ProviderRequest::fromPrompt('test-model', "  Keep\nspacing.  ");

        $this->assertSame("  Keep\nspacing.  ", $request->messages[0]['content']);
    }

    public function testRejectsEmptyModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('model must not be empty');

        ProviderRequest::fromPrompt('', 'hello');
    }

    public function testRejectsMissingMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one message');

        new ProviderRequest('test-model', []);
    }

    public function testRejectsBlankMessageContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new ProviderRequest('test-model', [
            ['role' => 'user', 'content' => '   '],
        ]);
    }
}
