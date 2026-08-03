<?php

namespace EloquentWorks\Passage\Enums;

enum EnrollmentState: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Check if the enrollment state is terminal (completed, expired, or cancelled).
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Expired, self::Cancelled], true);
    }
}
