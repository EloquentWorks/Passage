<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final readonly class StepCompleted
{
    /**
     * Create a new StepCompleted event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that completed the step.
     * @param  PassageStepProgress  $step  The step that was completed.
     * @param  Model|null  $actor  The actor responsible for the completion, if any.
     * @param  bool  $overridden  Whether the completion was overridden by an actor.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public PassageStepProgress $step, public ?Model $actor = null, public bool $overridden = false) {}
}
