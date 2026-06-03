<?php

declare(strict_types=1);

namespace Purple\Hooks;

interface RuntimeHook
{
    public function handle(HookEvent $event): HookResult;
}
