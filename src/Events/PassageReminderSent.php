<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;

final readonly class PassageReminderSent
{
    /**
     * Create a new PassageReminderSent event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment for which the reminder was sent.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment) {}
}
