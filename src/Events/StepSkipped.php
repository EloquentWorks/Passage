<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final readonly class StepSkipped
{
    /**
     * Create a new StepSkipped event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that skipped the step.
     * @param  PassageStepProgress  $step  The step that was skipped.
     * @param  Model|null  $actor  The actor responsible for the skip, if any.
     * @param  bool  $overridden  Whether the skip was overridden by an actor.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public PassageStepProgress $step, public ?Model $actor = null, public bool $overridden = false) {}
}
