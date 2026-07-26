<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Contracts;

interface PassageSubject
{
    public function getMorphClass(): string;

    public function getKey(): mixed;
}
