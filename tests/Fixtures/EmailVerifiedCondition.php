<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Tests\Fixtures;

use EloquentWorks\Passage\Contracts\StepCondition;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final class EmailVerifiedCondition implements StepCondition
{
    public function evaluate(Model $subject, PassageEnrollment $enrollment, PassageStepProgress $step): bool
    {
        return (bool) $subject->getAttribute('email_verified');
    }
}
