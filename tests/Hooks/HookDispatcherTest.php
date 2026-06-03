<?php

declare(strict_types=1);

namespace Purple\Tests\Hooks;

use Purple\Hooks\HookAction;
use Purple\Hooks\HookDispatcher;
use Purple\Hooks\HookEvent;
use Purple\Hooks\HookResult;
use Purple\Hooks\RuntimeHook;
use Purple\Tests\Testing\TestCase;

final class HookDispatcherTest extends TestCase
{
    public function testWarnAndModifyResultsAreAggregated(): void
    {
        $dispatcher = new HookDispatcher([
            new class () implements RuntimeHook {
                public function handle(HookEvent $event): HookResult
                {
                    return HookResult::warn('Heads up.');
                }
            },
            new class () implements RuntimeHook {
                public function handle(HookEvent $event): HookResult
                {
                    return HookResult::modify(['input' => ['sku' => 'SKU-2']]);
                }
            },
        ]);

        $result = $dispatcher->dispatch(new HookEvent('before_tool_call', 'run-1'));

        $this->assertSame(HookAction::Modify, $result->action);
        $this->assertSame(['Heads up.'], $result->warnings);
        $this->assertSame(['input' => ['sku' => 'SKU-2']], $result->modifications);
    }

    public function testTerminalHookResultsStopDispatch(): void
    {
        $dispatcher = new HookDispatcher([
            new class () implements RuntimeHook {
                public function handle(HookEvent $event): HookResult
                {
                    return HookResult::fail('Stop now.');
                }
            },
            new class () implements RuntimeHook {
                public function handle(HookEvent $event): HookResult
                {
                    return HookResult::allow();
                }
            },
        ]);

        $result = $dispatcher->dispatch(new HookEvent('before_run', 'run-1'));

        $this->assertSame(HookAction::Fail, $result->action);
        $this->assertSame('Stop now.', $result->message);
    }

    public function testTypedHookResultFactories(): void
    {
        $this->assertSame(HookAction::Allow, HookResult::allow()->action);
        $this->assertSame(HookAction::Block, HookResult::block('blocked')->action);
        $this->assertSame(HookAction::RequireApproval, HookResult::requireApproval('approval')->action);
        $this->assertSame(HookAction::Retry, HookResult::retry('retry')->action);
    }
}
