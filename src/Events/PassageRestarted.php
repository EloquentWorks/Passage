<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final readonly class PassageRestarted
{
    /**
     * Create a new PassageRestarted event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that was restarted.
     * @param  PassageEnrollment|null  $previous  The previous enrollment, if any.
     * @param  Model|null  $actor  The actor responsible for the restart, if any.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public ?PassageEnrollment $previous = null, public ?Model $actor = null) {}
}
