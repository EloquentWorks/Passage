<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Services;

use Closure;
use EloquentWorks\Passage\Contracts\StepCondition;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class ConditionEvaluator
{
    public function __construct(private Container $container) {}

    /** @param Closure|class-string<StepCondition>|null $condition */
    public function evaluate(
        Closure|string|null $condition,
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
        bool $default,
    ): bool {
        if ($condition === null) {
            return $default;
        }

        if ($condition instanceof Closure) {
            return (bool) $condition($subject, $enrollment, $step);
        }

        $resolved = $this->container->make($condition);

        if (! $resolved instanceof StepCondition) {
            throw new InvalidArgumentException("The [{$condition}] condition must implement StepCondition.");
        }

        return $resolved->evaluate($subject, $enrollment, $step);
    }
}
