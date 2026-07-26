<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Exceptions;

final class StepRetryLimitReached extends PassageException
{
    public static function for(string $step): self
    {
        return new self("The [{$step}] step has reached its retry limit.");
    }
}
