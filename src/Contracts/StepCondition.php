<?php

namespace EloquentWorks\Passage\Contracts;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

interface StepCondition
{
    /**
     * Evaluate the condition for the given subject, enrollment, and step.
     *
     * @param  Model  $subject  The subject to evaluate the condition against.
     * @param  PassageEnrollment  $enrollment  The enrollment associated with the subject.
     * @param  PassageStepProgress  $step  The step progress to evaluate the condition against.
     * @return bool True if the condition is satisfied, false otherwise.
     */
    public function evaluate(
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
    ): bool;
}
