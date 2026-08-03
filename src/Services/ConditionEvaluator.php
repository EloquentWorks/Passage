<?php

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
    /**
     * Create a new ConditionEvaluator instance.
     *
     * @param  Container  $container  The container instance for resolving conditions.
     * @return void
     */
    public function __construct(private Container $container) {}

    /**
     * Evaluate the given condition.
     *
     * @param  Closure|class-string<StepCondition>|null  $condition  The condition to evaluate.
     * @param  Model  $subject  The subject model.
     * @param  PassageEnrollment  $enrollment  The passage enrollment.
     * @param  PassageStepProgress  $step  The passage step progress.
     * @param  bool  $default  The default value to return if the condition is null.
     * @return bool  The result of the condition evaluation.
     */
    public function evaluate(
        Closure|string|null $condition,
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
        bool $default,
    ): bool {
        // If the condition is null, return the default value.
        if ($condition === null) {
            return $default;
        }

        // If the condition is a Closure, invoke it with the subject, enrollment, and step.
        if ($condition instanceof Closure) {
            return (bool) $condition($subject, $enrollment, $step);
        }

        // If the condition is a class-string, resolve it from the container and evaluate it.
        $resolved = $this->container->make($condition);

        // If the resolved condition does not implement StepCondition, throw an exception.
        if (! $resolved instanceof StepCondition) {
            throw new InvalidArgumentException("The [{$condition}] condition must implement StepCondition.");
        }

        // Evaluate the resolved condition with the subject, enrollment, and step.
        return $resolved->evaluate($subject, $enrollment, $step);
    }
}
