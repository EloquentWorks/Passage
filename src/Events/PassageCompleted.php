<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;

final readonly class PassageCompleted
{
    /**
     * Create a new PassageCompleted event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that was completed.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment) {}
}
