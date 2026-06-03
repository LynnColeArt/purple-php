<?php

declare(strict_types=1);

namespace Purple\Tests\Testing;

use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;
use Purple\Testing\FakeProvider;

final class FakeProviderTest extends TestCase
{
    public function testReturnsQueuedResponseAndRecordsRequest(): void
    {
        $provider = new FakeProvider([
            new ProviderResponse('structured answer'),
        ]);

        $request = ProviderRequest::fromPrompt('fake-model', 'Classify this.');
        $response = $provider->complete($request);

        $this->assertSame('structured answer', $response->content);
        $this->assertSame([$request], $provider->requests());
    }

    public function testDefaultResponseIsDeterministic(): void
    {
        $provider = new FakeProvider();
        $request = ProviderRequest::fromPrompt('fake-model', 'Hello.');

        $this->assertSame('[fake:fake-model] Hello.', $provider->complete($request)->content);
        $this->assertSame('[fake:fake-model] Hello.', $provider->complete($request)->content);
    }
}
