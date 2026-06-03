<?php

declare(strict_types=1);

namespace Purple\Contracts\Provider;

interface Provider
{
    public function complete(ProviderRequest $request): ProviderResponse;
}
