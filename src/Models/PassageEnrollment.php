<?php

declare(strict_types=1);

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
    /** @var list<string> */
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

    public function getTable(): string
    {
        return (string) config('passage.tables.enrollments', 'passage_enrollments');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<PassageStepProgress, $this> */
    public function steps(): HasMany
    {
        $model = (string) config('passage.models.step', PassageStepProgress::class);

        return $this->hasMany($model, 'enrollment_id');
    }

    /** @return HasMany<PassageAudit, $this> */
    public function audits(): HasMany
    {
        $model = (string) config('passage.models.audit', PassageAudit::class);

        return $this->hasMany($model, 'enrollment_id');
    }

    /** @param Builder<PassageEnrollment> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('state', [
            EnrollmentState::Pending->value,
            EnrollmentState::InProgress->value,
            EnrollmentState::Blocked->value,
        ]);
    }

    /** @param Builder<PassageEnrollment> $query */
    public function scopeForPassage(Builder $query, string $passage): void
    {
        $query->where('passage_key', $passage);
    }

    public function isComplete(): bool
    {
        return $this->state === EnrollmentState::Completed;
    }

    public function isTerminal(): bool
    {
        return $this->state->isTerminal();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
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
