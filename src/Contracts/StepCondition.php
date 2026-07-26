<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Contracts;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

interface StepCondition
{
    public function evaluate(
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
    ): bool;
}
