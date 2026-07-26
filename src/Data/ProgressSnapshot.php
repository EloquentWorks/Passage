<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Data;

use EloquentWorks\Passage\Enums\EnrollmentState;

final readonly class ProgressSnapshot
{
    public function __construct(
        public string $passage,
        public int $version,
        public int $cycle,
        public EnrollmentState $state,
        public int $requiredTotal,
        public int $requiredCompleted,
        public int $total,
        public int $satisfied,
        public int $percentage,
        public ?string $nextStep,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'passage' => $this->passage,
            'version' => $this->version,
            'cycle' => $this->cycle,
            'state' => $this->state->value,
            'required_total' => $this->requiredTotal,
            'required_completed' => $this->requiredCompleted,
            'total' => $this->total,
            'satisfied' => $this->satisfied,
            'percentage' => $this->percentage,
            'next_step' => $this->nextStep,
        ];
    }
}
