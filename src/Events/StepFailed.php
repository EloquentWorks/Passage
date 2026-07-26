<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Events;

use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final readonly class StepFailed
{
    public function __construct(public PassageEnrollment $enrollment, public PassageStepProgress $step, public ?Model $actor = null, public ?string $reason = null) {}
}
