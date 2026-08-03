<?php

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final readonly class StepFailed
{
    /**
     * Create a new StepFailed event instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment that failed the step.
     * @param  PassageStepProgress  $step  The step that was failed.
     * @param  Model|null  $actor  The actor responsible for the failure, if any.
     * @param  string|null  $reason  The reason for the failure, if any.
     * @return void
     */
    public function __construct(public PassageEnrollment $enrollment, public PassageStepProgress $step, public ?Model $actor = null, public ?string $reason = null) {}
}
