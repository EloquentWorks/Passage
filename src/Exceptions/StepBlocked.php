<?php

namespace EloquentWorks\Passage\Exceptions;

final class StepBlocked extends PassageException
{
    /**
     * Create a new StepBlocked exception instance.
     *
     * @param  string  $step  The step that is blocked.
     * @param  list<string>  $prerequisites  The prerequisites that are blocking the step.
     */
    public static function byPrerequisites(string $step, array $prerequisites): self
    {
        // Format the prerequisites as a comma-separated list
        return new self(sprintf(
            'The [%s] step is blocked by: %s.',
            $step,
            implode(', ', $prerequisites),
        ));
    }
}
