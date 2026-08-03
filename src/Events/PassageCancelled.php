<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final readonly class PassageCancelled
{
    /**
     * Create a new PassageCancelled event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that was cancelled.
     * @param  Model|null  $actor  The actor responsible for the cancellation, if any.
     * @param  string|null  $reason  The reason for the cancellation, if any.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public ?Model $actor = null, public ?string $reason = null) {}
}
