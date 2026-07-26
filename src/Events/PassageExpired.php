<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;

final readonly class PassageExpired
{
    public function __construct(public PassageEnrollment $enrollment) {}
}
