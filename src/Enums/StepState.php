<?php

namespace EloquentWorks\Passage\Enums;

enum StepState: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Failed = 'failed';
    case Blocked = 'blocked';

    /**
     * Check if the step state is satisfied (completed or skipped).
     *
     * @return bool
     */
    public function isSatisfied(): bool
    {
        return in_array($this, [self::Completed, self::Skipped], true);
    }

    /**
     * Check if the step state is terminal (completed or skipped).
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Skipped], true);
    }
}
