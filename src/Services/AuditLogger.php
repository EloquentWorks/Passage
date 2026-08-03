<?php

namespace EloquentWorks\Passage\Services;

use EloquentWorks\Passage\Models\PassageAudit;
use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * Record an audit event for a passage enrollment.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment for which the event is being recorded.
     * @param  string  $event  The event name.
     * @param  string|null  $step  The step key associated with the event, if any.
     * @param  Model|null  $actor  The actor responsible for the event, if any.
     * @param  array<string, mixed>  $data  Additional data associated with the event.
     * @return PassageAudit The created audit record.
     */
    public function record(
        PassageEnrollment $enrollment,
        string $event,
        ?string $step = null,
        ?Model $actor = null,
        array $data = [],
    ): PassageAudit {
        /** @var class-string<PassageAudit> $model */
        $model = (string) config('passage.models.audit', PassageAudit::class);

        // Create a new audit record with the provided information.
        return $model::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'passage_key' => $enrollment->passage_key,
            'step_key' => $step,
            'event' => $event,
            'subject_type' => $enrollment->subject_type,
            'subject_id' => $enrollment->subject_id,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor !== null ? (string) $actor->getKey() : null,
            'data' => $data,
            'occurred_at' => now(),
        ]);
    }
}
