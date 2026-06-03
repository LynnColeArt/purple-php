<?php

declare(strict_types=1);

namespace Purple\Tool;

enum ToolSideEffectLevel: string
{
    case None = 'none';
    case Read = 'read';
    case Write = 'write';
    case External = 'external';
}
