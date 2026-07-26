<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Exceptions;

final class PassageNotDefined extends PassageException
{
    public static function for(string $key): self
    {
        return new self("The [{$key}] passage has not been defined.");
    }
}
