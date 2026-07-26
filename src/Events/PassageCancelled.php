<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final readonly class PassageCancelled
{
    public function __construct(public PassageEnrollment $enrollment, public ?Model $actor = null, public ?string $reason = null) {}
}
