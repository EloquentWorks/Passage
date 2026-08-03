<?php

namespace EloquentWorks\Passage\Data;

use EloquentWorks\Passage\Enums\EnrollmentState;

final readonly class ProgressSnapshot
{
    /**
     * Create a new ProgressSnapshot instance.
     *
     * @param  string  $passage  The unique key of the passage.
     * @param  int  $version  The version of the passage.
     * @param  int  $cycle  The cycle number of the passage.
     * @param  EnrollmentState  $state  The current state of the enrollment.
     * @param  int  $requiredTotal  The total number of required steps in the passage.
     * @param  int  $requiredCompleted  The number of required steps that have been completed.
     * @param  int  $total  The total number of steps in the passage.
     * @param  int  $satisfied  The number of steps that have been satisfied (completed or skipped).
     * @param  int  $percentage  The percentage of progress made in the passage.
     * @param  string|null  $nextStep  The identifier of the next step to be completed, if any.
     * @return void
     */
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

    /**
     * Convert the ProgressSnapshot instance to an array.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        // Convert the ProgressSnapshot instance to an associative array representation.
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
