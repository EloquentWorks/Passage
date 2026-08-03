<?php

namespace EloquentWorks\Passage\Models;

use EloquentWorks\Passage\Enums\EnrollmentState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $uuid
 * @property string $subject_type
 * @property string $subject_id
 * @property string $passage_key
 * @property int $passage_version
 * @property int $cycle
 * @property EnrollmentState $state
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expired_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $last_reminded_at
 * @property-read Model $subject
 * @property-read Collection<int, PassageStepProgress> $steps
 * @property-read Collection<int, PassageAudit> $audits
 */
class PassageEnrollment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'subject_type',
        'subject_id',
        'passage_key',
        'passage_version',
        'cycle',
        'state',
        'metadata',
        'started_at',
        'completed_at',
        'expired_at',
        'cancelled_at',
        'due_at',
        'last_activity_at',
        'last_reminded_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    public function getTable(): string
    {
        // Get the table name for the enrollments from the configuration, defaulting to 'passage_enrollments' if not specified
        return (string) config('passage.tables.enrollments', 'passage_enrollments');
    }

    /**
     * Get the subject associated with the enrollment.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        // Return a polymorphic relationship for the subject associated with this enrollment
        return $this->morphTo();
    }

    /**
     * Get the steps associated with the enrollment.
     *
     * @return HasMany<PassageStepProgress, $this>
     */
    public function steps(): HasMany
    {
        // Get the model class for steps from the configuration, defaulting to PassageStepProgress if not specified
        $model = (string) config('passage.models.step', PassageStepProgress::class);

        // Return a HasMany relationship for the steps associated with this enrollment, using the enrollment_id foreign key
        return $this->hasMany($model, 'enrollment_id');
    }

    /**
     * Get the audits associated with the enrollment.
     *
     * @return HasMany<PassageAudit, $this>
     */
    public function audits(): HasMany
    {
        // Get the model class for audits from the configuration, defaulting to PassageAudit if not specified
        $model = (string) config('passage.models.audit', PassageAudit::class);

        // Return a HasMany relationship for the audits associated with this enrollment, using the enrollment_id foreign key
        return $this->hasMany($model, 'enrollment_id');
    }

    /**
     * Scope a query to only include active enrollments.
     *
     * @param  Builder<PassageEnrollment>  $query
     * @return void
     */
    public function scopeActive(Builder $query): void
    {
        // Filter the query to only include enrollments that are in active states (Pending, InProgress, Blocked)
        $query->whereIn('state', [
            EnrollmentState::Pending->value,
            EnrollmentState::InProgress->value,
            EnrollmentState::Blocked->value,
        ]);
    }

    /**
     * Scope a query to only include enrollments for a specific passage.
     *
     * @param  Builder<PassageEnrollment>  $query
     * @param  string  $passage
     * @return void
     */
    public function scopeForPassage(Builder $query, string $passage): void
    {
        // Filter the query to only include enrollments for the specified passage key
        $query->where('passage_key', $passage);
    }

    /**
     * Determine if the enrollment is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        // Check if the enrollment state is completed
        return $this->state === EnrollmentState::Completed;
    }

    /**
     * Determine if the enrollment is expired.
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        // Check if the enrollment state is terminal (completed, expired, or cancelled)
        return $this->state->isTerminal();
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Define the casts for the model's attributes to ensure proper data types when accessing them
        return [
            'passage_version' => 'integer',
            'cycle' => 'integer',
            'state' => EnrollmentState::class,
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'due_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_reminded_at' => 'datetime',
        ];
    }
}
