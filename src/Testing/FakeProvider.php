<?php

declare(strict_types=1);

namespace Purple\Testing;

use Purple\Contracts\Provider\Provider;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Provider\ProviderResponse;

final class FakeProvider implements Provider
{
    /** @var list<ProviderRequest> */
    private array $requests = [];

    /** @var list<ProviderResponse> */
    private array $responses;

    /**
     * @param list<ProviderResponse> $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public static function replying(string $content): self
    {
        return new self([new ProviderResponse($content)]);
    }

    public function complete(ProviderRequest $request): ProviderResponse
    {
        $this->requests[] = $request;

        if ($this->responses !== []) {
            return array_shift($this->responses);
        }

        return new ProviderResponse($this->defaultContent($request), [
            'fake' => true,
        ]);
    }

    /**
     * @return list<ProviderRequest>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    private function defaultContent(ProviderRequest $request): string
    {
        $lastMessage = $request->messages[array_key_last($request->messages)];

        return sprintf('[fake:%s] %s', $request->model, $lastMessage['content']);
    }
}
