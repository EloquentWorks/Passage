<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final readonly class StepStarted
{
    /**
     * Create a new StepStarted event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that started the step.
     * @param  PassageStepProgress  $step  The step that was started.
     * @param  Model|null  $actor  The actor responsible for starting the step, if any.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public PassageStepProgress $step, public ?Model $actor = null) {}
}
