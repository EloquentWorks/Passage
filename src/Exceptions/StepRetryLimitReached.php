<?php

namespace EloquentWorks\Passage\Exceptions;

final class StepRetryLimitReached extends PassageException
{
    /**
     * Create a new StepRetryLimitReached exception instance.
     *
     * @param  string  $step  The step that has reached its retry limit.
     */
    public static function for(string $step): self
    {
        // Create a new StepRetryLimitReached exception instance with a message indicating that the step has reached its retry limit.
        return new self("The [{$step}] step has reached its retry limit.");
    }
}
