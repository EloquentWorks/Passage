<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final readonly class PassageEnrolled
{
    /**
     * Create a new PassageEnrolled event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that was created.
     * @param  Model|null  $actor  The actor responsible for the enrollment, if any.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public ?Model $actor = null) {}
}
