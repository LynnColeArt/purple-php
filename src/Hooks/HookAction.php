<?php

declare(strict_types=1);

namespace Purple\Hooks;

enum HookAction: string
{
    case Allow = 'allow';
    case Block = 'block';
    case Warn = 'warn';
    case Modify = 'modify';
    case RequireApproval = 'require_approval';
    case Retry = 'retry';
    case Fail = 'fail';
}
