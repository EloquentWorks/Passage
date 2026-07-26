<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Models;

use EloquentWorks\Passage\Enums\StepState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property string $step_key
 * @property int $position
 * @property bool $required
 * @property StepState $state
 * @property int $attempts
 * @property string|null $failure_reason
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $skipped_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property-read PassageEnrollment $enrollment
 */
class PassageStepProgress extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'enrollment_id',
        'step_key',
        'position',
        'required',
        'state',
        'attempts',
        'failure_reason',
        'data',
        'started_at',
        'completed_at',
        'skipped_at',
        'failed_at',
        'due_at',
    ];

    public function getTable(): string
    {
        return (string) config('passage.tables.steps', 'passage_step_progress');
    }

    /** @return BelongsTo<PassageEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        return $this->belongsTo($model, 'enrollment_id');
    }

    /** @param Builder<PassageStepProgress> $query */
    public function scopeSatisfied(Builder $query): void
    {
        $query->whereIn('state', [StepState::Completed->value, StepState::Skipped->value]);
    }

    public function isSatisfied(): bool
    {
        return $this->state->isSatisfied();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'required' => 'boolean',
            'state' => StepState::class,
            'attempts' => 'integer',
            'data' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'failed_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }
}
