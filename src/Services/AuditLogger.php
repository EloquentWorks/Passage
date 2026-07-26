<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Services;

use EloquentWorks\Passage\Models\PassageAudit;
use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /** @param array<string, mixed> $data */
    public function record(
        PassageEnrollment $enrollment,
        string $event,
        ?string $step = null,
        ?Model $actor = null,
        array $data = [],
    ): PassageAudit {
        /** @var class-string<PassageAudit> $model */
        $model = (string) config('passage.models.audit', PassageAudit::class);

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
