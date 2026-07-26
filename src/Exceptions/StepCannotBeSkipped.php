<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Exceptions;

final class StepCannotBeSkipped extends PassageException
{
    public static function required(string $step): self
    {
        return new self("The required [{$step}] step cannot be skipped without an override.");
    }
}
