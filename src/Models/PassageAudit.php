<?php

declare(strict_types=1);

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
    /** @var list<string> */
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

    public function getTable(): string
    {
        return (string) config('passage.tables.audits', 'passage_audits');
    }

    /** @return BelongsTo<PassageEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        return $this->belongsTo($model, 'enrollment_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
