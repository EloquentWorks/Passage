<?php

namespace EloquentWorks\Passage\Exceptions;

final class StepCannotBeSkipped extends PassageException
{
    /**
     * Create a new StepCannotBeSkipped exception instance.
     *
     * @param  string  $step  The step that cannot be skipped.
     */
    public static function required(string $step): self
    {
        // Create a new StepCannotBeSkipped exception instance with a message indicating that the required step
        // cannot be skipped without an override.
        return new self("The required [{$step}] step cannot be skipped without an override.");
    }
}
