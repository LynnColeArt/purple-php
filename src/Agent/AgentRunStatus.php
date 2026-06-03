<?php

declare(strict_types=1);

namespace Purple\Agent;

enum AgentRunStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
    case ApprovalRequired = 'approval_required';
    case BudgetExceeded = 'budget_exceeded';
    case PolicyDenied = 'policy_denied';
}
