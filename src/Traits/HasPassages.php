<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Traits;

use EloquentWorks\Passage\Data\ProgressSnapshot;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasPassages
{
    /** @return MorphMany<PassageEnrollment, $this> */
    public function passageEnrollments(): MorphMany
    {
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        return $this->morphMany($model, 'subject');
    }

    /** @param array<string, mixed> $metadata */
    public function startPassage(string $passage, array $metadata = []): PassageEnrollment
    {
        return app(PassageManager::class)->enroll($this, $passage, $metadata);
    }

    public function passage(string $passage): ?PassageEnrollment
    {
        return app(PassageManager::class)->current($this, $passage);
    }

    public function activePassage(string $passage): ?PassageEnrollment
    {
        return app(PassageManager::class)->active($this, $passage);
    }

    public function passageProgress(string $passage): ProgressSnapshot
    {
        return app(PassageManager::class)->progress($this, $passage);
    }

    public function nextPassageStep(string $passage): ?PassageStepProgress
    {
        return app(PassageManager::class)->nextStep($this, $passage);
    }

    /** @param array<string, mixed> $data */
    public function completePassageStep(string $passage, string $step, array $data = []): PassageStepProgress
    {
        return app(PassageManager::class)->completeStep($this, $passage, $step, $data);
    }

    /** @param array<string, mixed> $data */
    public function skipPassageStep(string $passage, string $step, array $data = []): PassageStepProgress
    {
        return app(PassageManager::class)->skipStep($this, $passage, $step, $data);
    }

    /** @param array<string, mixed> $data */
    public function failPassageStep(string $passage, string $step, string $reason, array $data = []): PassageStepProgress
    {
        return app(PassageManager::class)->failStep($this, $passage, $step, $reason, $data);
    }

    public function hasCompletedPassage(string $passage): bool
    {
        return $this->passage($passage)?->isComplete() === true;
    }

    /** @param array<string, mixed> $metadata */
    public function restartPassage(string $passage, array $metadata = []): PassageEnrollment
    {
        return app(PassageManager::class)->restart($this, $passage, $metadata);
    }
}
