<?php

namespace EloquentWorks\Passage\Models;

use EloquentWorks\Passage\Enums\StepState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $skipped_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $due_at
 * @property-read PassageEnrollment $enrollment
 */
class PassageStepProgress extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    public function getTable(): string
    {
        // Get the table name for the step progress model from the configuration, defaulting to 'passage_step_progress'.
        return (string) config('passage.tables.steps', 'passage_step_progress');
    }

    /**
     * Get the enrollment that owns the step progress.
     *
     * @return BelongsTo<PassageEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        // Get the enrollment model class from the configuration, defaulting to PassageEnrollment.
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        // Define a belongsTo relationship between the step progress and the enrollment model using the enrollment_id foreign key.
        return $this->belongsTo($model, 'enrollment_id');
    }

    /**
     * Scope a query to only include satisfied step progress.
     *
     * @param  Builder<PassageStepProgress>  $query
     */
    public function scopeSatisfied(Builder $query): void
    {
        // Filter the query to only include step progress that is satisfied (completed or skipped).
        $query->whereIn('state', [StepState::Completed->value, StepState::Skipped->value]);
    }

    /**
     * Determine if the step progress is satisfied.
     */
    public function isSatisfied(): bool
    {
        // Check if the current state of the step progress is satisfied (completed or skipped).
        return $this->state->isSatisfied();
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Define the casts for the model's attributes to ensure proper data types.
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
