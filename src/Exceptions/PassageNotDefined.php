<?php

namespace EloquentWorks\Passage\Exceptions;

final class PassageNotDefined extends PassageException
{
    /**
     * Create a new PassageNotDefined exception instance.
     *
     * @param  string  $key  The key of the passage that has not been defined.
     */
    public static function for(string $key): self
    {
        // Create a new PassageNotDefined exception instance with a message indicating that the passage has not been defined.
        return new self("The [{$key}] passage has not been defined.");
    }
}
