<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Enums;

enum StepState: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Failed = 'failed';
    case Blocked = 'blocked';

    public function isSatisfied(): bool
    {
        return in_array($this, [self::Completed, self::Skipped], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Skipped], true);
    }
}
