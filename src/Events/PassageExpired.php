<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;

final readonly class PassageExpired
{
    /**
     * Create a new PassageExpired event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that has expired.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment) {}
}
