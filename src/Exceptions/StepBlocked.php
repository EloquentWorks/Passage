<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Exceptions;

final class StepBlocked extends PassageException
{
    /** @param list<string> $prerequisites */
    public static function byPrerequisites(string $step, array $prerequisites): self
    {
        return new self(sprintf(
            'The [%s] step is blocked by: %s.',
            $step,
            implode(', ', $prerequisites),
        ));
    }
}
