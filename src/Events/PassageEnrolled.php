<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final readonly class PassageEnrolled
{
    public function __construct(public PassageEnrollment $enrollment, public ?Model $actor = null) {}
}
