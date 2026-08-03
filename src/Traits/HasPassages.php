<?php

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
    /**
     * Get all of the passage enrollments for the model.
     *
     * @return MorphMany<PassageEnrollment, $this>
     */
    public function passageEnrollments(): MorphMany
    {
        // Get the enrollment model class from the configuration, defaulting to PassageEnrollment if not set.
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        // Return a morphMany relationship for the enrollments, using 'subject' as the morph name.
        return $this->morphMany($model, 'subject');
    }

    /**
     * Start a new passage enrollment for the model.
     *
     * @param  string  $passage
     * @param  array<string, mixed>  $metadata
     * @return PassageEnrollment
     */
    public function startPassage(string $passage, array $metadata = []): PassageEnrollment
    {
        // Use the PassageManager to enroll the model in the specified passage with the provided metadata.
        return app(PassageManager::class)->enroll($this, $passage, $metadata);
    }

    /**
     * Get the current passage enrollment for the model.
     *
     * @param  string  $passage
     * @return PassageEnrollment|null
     */
    public function passage(string $passage): ?PassageEnrollment
    {
        // Use the PassageManager to retrieve the current enrollment for the specified passage.
        return app(PassageManager::class)->current($this, $passage);
    }

    /**
     * Get the active passage enrollment for the model.
     *
     * @param  string  $passage
     * @return PassageEnrollment|null
     */
    public function activePassage(string $passage): ?PassageEnrollment
    {
        // Use the PassageManager to retrieve the active enrollment for the specified passage.
        return app(PassageManager::class)->active($this, $passage);
    }
    
    /**
     * Get the progress snapshot for the specified passage.
     *
     * @param  string  $passage
     * @return ProgressSnapshot
     */
    public function passageProgress(string $passage): ProgressSnapshot
    {
        // Use the PassageManager to retrieve the progress snapshot for the specified passage.
        return app(PassageManager::class)->progress($this, $passage);
    }

    /**
     * Get the next step in the specified passage for the model.
     *
     * @param  string  $passage
     * @return PassageStepProgress|null
     */
    public function nextPassageStep(string $passage): ?PassageStepProgress
    {
        // Use the PassageManager to retrieve the next step in the specified passage for the model.
        return app(PassageManager::class)->nextStep($this, $passage);
    }

    /**
     * Complete the specified step in the passage for the model.
     *
     * @param  string  $passage
     * @param  string  $step
     * @param  array<string, mixed>  $data
     * @return PassageStepProgress
     */
    public function completePassageStep(string $passage, string $step, array $data = []): PassageStepProgress
    {
        // Use the PassageManager to complete the specified step in the passage for the model.
        return app(PassageManager::class)->completeStep($this, $passage, $step, $data);
    }

    /**
     * Skip the specified step in the passage for the model.
     *
     * @param  string  $passage
     * @param  string  $step
     * @param  array<string, mixed>  $data
     * @return PassageStepProgress
     */
    public function skipPassageStep(string $passage, string $step, array $data = []): PassageStepProgress
    {
        // Use the PassageManager to skip the specified step in the passage for the model.
        return app(PassageManager::class)->skipStep($this, $passage, $step, $data);
    }

    /**
     * Fail the specified step in the passage for the model.
     *
     * @param  string  $passage
     * @param  string  $step
     * @param  string  $reason
     * @param  array<string, mixed>  $data
     * @return PassageStepProgress
     */
    public function failPassageStep(string $passage, string $step, string $reason, array $data = []): PassageStepProgress
    {
        // Use the PassageManager to fail the specified step in the passage for the model.
        return app(PassageManager::class)->failStep($this, $passage, $step, $reason, $data);
    }

    /**
     * Check if the model has completed the specified passage.
     *
     * @param  string  $passage
     * @return bool
     */
    public function hasCompletedPassage(string $passage): bool
    {
        // Use the PassageManager to check if the model has completed the specified passage.
        return $this->passage($passage)?->isComplete() === true;
    }

    /**
     * Restart the specified passage for the model.
     *
     * @param  string  $passage
     * @param  array<string, mixed>  $metadata
     * @return PassageEnrollment
     */
    public function restartPassage(string $passage, array $metadata = []): PassageEnrollment
    {
        // Use the PassageManager to restart the specified passage for the model.
        return app(PassageManager::class)->restart($this, $passage, $metadata);
    }
}
