<?php

namespace EloquentWorks\Passage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $enrollment_id
 * @property string $passage_key
 * @property string|null $step_key
 * @property string $event
 * @property string $subject_type
 * @property string $subject_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property-read PassageEnrollment|null $enrollment
 * @property-read Model $subject
 * @property-read Model|null $actor
 */
class PassageAudit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enrollment_id',
        'passage_key',
        'step_key',
        'event',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'data',
        'occurred_at',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        // The table name is configurable via the 'passage.tables.audits' configuration option.
        return (string) config('passage.tables.audits', 'passage_audits');
    }

    /**
     * Get the enrollment associated with the audit.
     *
     * @return BelongsTo<PassageEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        // The enrollment model is configurable via the 'passage.models.enrollment' configuration option.
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        // The enrollment is a standard belongs-to relationship, linking the audit to the specific enrollment it pertains to.
        return $this->belongsTo($model, 'enrollment_id');
    }

    /**
     * Get the subject associated with the audit.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        // The subject is a polymorphic relationship, allowing for different types of models to be
        return $this->morphTo();
    }

    /**
     * Get the actor associated with the audit.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        // The actor is a polymorphic relationship, allowing for different types of models to be
        // associated as the actor of the audit event.
        return $this->morphTo();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Cast the 'data' attribute to an array and 'occurred_at' to a datetime object for easier manipulation.
        return [
            'data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
