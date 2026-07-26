<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Enums;

enum EnrollmentState: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Expired, self::Cancelled], true);
    }
}
