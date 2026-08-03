<?php

namespace EloquentWorks\Passage\Contracts;

interface PassageSubject
{
    /**
     * Get the unique key of the passage subject.
     *
     * @return string The unique key of the passage subject.
     */
    public function getMorphClass(): string;

    /**
     * Get the unique identifier of the passage subject.
     *
     * @return mixed The unique identifier of the passage subject.
     */
    public function getKey(): mixed;
}
