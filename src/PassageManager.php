<?php

namespace EloquentWorks\Passage;

use EloquentWorks\Passage\Data\ProgressSnapshot;
use EloquentWorks\Passage\Definitions\PassageDefinition;
use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Enums\StepState;
use EloquentWorks\Passage\Events\PassageCancelled;
use EloquentWorks\Passage\Events\PassageCompleted;
use EloquentWorks\Passage\Events\PassageEnrolled;
use EloquentWorks\Passage\Events\PassageExpired;
use EloquentWorks\Passage\Events\PassageRestarted;
use EloquentWorks\Passage\Events\StepCompleted;
use EloquentWorks\Passage\Events\StepFailed;
use EloquentWorks\Passage\Events\StepSkipped;
use EloquentWorks\Passage\Events\StepStarted;
use EloquentWorks\Passage\Exceptions\StepBlocked;
use EloquentWorks\Passage\Exceptions\StepCannotBeSkipped;
use EloquentWorks\Passage\Exceptions\StepRetryLimitReached;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use EloquentWorks\Passage\Services\AuditLogger;
use EloquentWorks\Passage\Services\ConditionEvaluator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class PassageManager
{
    /**
     * Create a new PassageManager instance.
     *
     * @param  PassageRegistry  $registry  The registry of passage definitions.
     * @param  ConditionEvaluator  $conditions  The service for evaluating conditions.
     * @param  AuditLogger  $audits  The service for logging audit events.
     * @param  Dispatcher  $events  The event dispatcher for firing events.
     * @return void
     */
    public function __construct(
        private PassageRegistry $registry,
        private ConditionEvaluator $conditions,
        private AuditLogger $audits,
        private Dispatcher $events,
    ) {}

    /**
     * Get the passage registry.
     */
    public function registry(): PassageRegistry
    {
        // Return the passage registry instance.
        return $this->registry;
    }

    /**
     * Get the definition of a passage by its key.
     *
     * @param  string  $key  The key of the passage definition.
     */
    public function definition(string $key): PassageDefinition
    {
        // Return the passage definition from the registry by its key.
        return $this->registry->get($key);
    }

    /**
     * Enroll a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage to enroll in.
     * @param  array<string, mixed>  $metadata  Additional metadata for the enrollment.
     * @param  bool  $forceNew  Whether to force a new enrollment even if one exists.
     * @param  Model|null  $actor  The actor responsible for the enrollment, if any.
     * @return PassageEnrollment The created or existing passage enrollment.
     */
    public function enroll(
        Model $subject,
        string $passage,
        array $metadata = [],
        bool $forceNew = false,
        ?Model $actor = null,
    ): PassageEnrollment {
        // Get the passage definition and check for existing enrollment
        $definition = $this->definition($passage);
        $existing = $this->current($subject, $passage);

        // If not forcing a new enrollment and an existing one is found, return it if
        // it's not terminal or if the passage is repeatable
        if (! $forceNew && $existing !== null) {
            if (! $existing->isTerminal() || ! $definition->isRepeatable()) {
                return $existing;
            }
        }

        // Create a new enrollment within a database transaction to ensure atomicity
        return DB::transaction(function () use ($subject, $definition, $metadata, $actor): PassageEnrollment {
            /** @var class-string<PassageEnrollment> $model */
            $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

            // Determine the next cycle number for the enrollment based on existing enrollments
            $cycle = (int) $model::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', (string) $subject->getKey())
                ->where('passage_key', $definition->key())
                ->max('cycle') + 1;

            // Create a new enrollment record in the database with the provided metadata and passage definition
            $enrollment = $model::query()->create([
                'uuid' => (string) Str::uuid(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'passage_key' => $definition->key(),
                'passage_version' => $definition->revision(),
                'cycle' => $cycle,
                'state' => EnrollmentState::InProgress,
                'metadata' => [...$definition->meta(), ...$metadata],
                'started_at' => now(),
                'due_at' => $definition->dueMinutes() !== null
                    ? now()->addMinutes($definition->dueMinutes())
                    : null,
                'last_activity_at' => now(),
            ]);

            // Create step progress records for each step in the passage definition
            foreach ($definition->steps() as $step) {
                $this->createStepProgress($enrollment, $step);
            }

            // Record the enrollment event in the audit log and dispatch the PassageEnrolled event
            $this->audits->record($enrollment, 'passage.enrolled', actor: $actor);
            $this->events->dispatch(new PassageEnrolled($enrollment, $actor));

            // Recalculate the enrollment state and return the refreshed enrollment instance
            return $this->recalculate($enrollment);
        });
    }

    /**
     * Get the current enrollment for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return PassageEnrollment|null The current enrollment or null if none exists.
     */
    public function current(Model $subject, string $passage): ?PassageEnrollment
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        // Query the database for the latest enrollment for the given subject and passage
        return $model::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('passage_key', $passage)
            ->latest('cycle')
            ->first();
    }

    /**
     * Get the active enrollment for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return PassageEnrollment|null The active enrollment or null if none exists.
     */
    public function active(Model $subject, string $passage): ?PassageEnrollment
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        // Query the database for the latest active enrollment for the given subject and passage
        return $model::query()
            ->active()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('passage_key', $passage)
            ->latest('cycle')
            ->first();
    }

    /**
     * Get the current enrollment for a subject in a passage, or enroll them if none exists.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return PassageEnrollment The current or newly created enrollment.
     */
    public function getOrEnroll(Model $subject, string $passage): PassageEnrollment
    {
        // Return the current enrollment if it exists, otherwise enroll the subject in the passage
        return $this->current($subject, $passage) ?? $this->enroll($subject, $passage);
    }

    /**
     * Start a step for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  string  $step  The key of the step to start.
     * @param  array<string, mixed>  $data  Additional data to associate with the step progress.
     * @param  Model|null  $actor  The actor model.
     * @return PassageStepProgress The step progress instance.
     */
    public function startStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
    ): PassageStepProgress {
        // Get or enroll the subject in the passage, retrieve the step progress and definition
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        // Validate that the prerequisites for the step are satisfied and that retry is available
        $this->assertPrerequisitesSatisfied($enrollment, $definition);
        $this->assertRetryAvailable($progress, $definition);

        // If the step is already completed, return the progress without making changes
        if ($progress->state === StepState::Completed) {
            return $progress;
        }

        // Update the step progress to indicate that it is in progress, increment attempts, and record the start time
        $progress->forceFill([
            'state' => StepState::InProgress,
            'attempts' => $progress->attempts + 1,
            'started_at' => $progress->started_at ?? now(),
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        // Update the enrollment state to indicate that it is in progress and record the last activity time
        $enrollment->forceFill([
            'state' => EnrollmentState::InProgress,
            'last_activity_at' => now(),
        ])->save();

        // Record the step start event in the audit log and dispatch the StepStarted event
        $this->audits->record($enrollment, 'step.started', $step, $actor, $data);
        $this->events->dispatch(new StepStarted($enrollment, $progress, $actor));

        // Recalculate the enrollment state and return the refreshed step progress instance
        return $progress->refresh();
    }

    /**
     * Complete a step for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  string  $step  The key of the step to complete.
     * @param  array<string, mixed>  $data  Additional data to associate with the step progress.
     * @param  Model|null  $actor  The actor model.
     * @param  bool  $force  Whether to force completion of the step.
     * @return PassageStepProgress The step progress instance.
     */
    public function completeStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
        bool $force = false,
    ): PassageStepProgress {
        // Get or enroll the subject in the passage, retrieve the step progress and definition
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        // Validate that the prerequisites for the step are satisfied unless forcing completion
        if (! $force) {
            $this->assertPrerequisitesSatisfied($enrollment, $definition);
        }

        // Validate that retry is available for the step unless forcing completion
        if ($progress->state === StepState::Completed) {
            return $progress;
        }

        // Update the step progress to indicate that it is completed, record the
        // completion time, and clear any failure information
        $progress->forceFill([
            'state' => StepState::Completed,
            'started_at' => $progress->started_at ?? now(),
            'completed_at' => now(),
            'skipped_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        // Update the enrollment state to indicate that it is in progress and record the last activity time
        $enrollment->forceFill(['last_activity_at' => now()])->save();

        // Record the step completion event in the audit log and dispatch the StepCompleted event
        $this->audits->record($enrollment, $force ? 'step.overridden' : 'step.completed', $step, $actor, $data);
        $this->events->dispatch(new StepCompleted($enrollment, $progress, $actor, $force));
        $this->recalculate($enrollment);

        // Return the refreshed step progress instance
        return $progress->refresh();
    }

    /**
     * Skip a step for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  string  $step  The key of the step to skip.
     * @param  array<string, mixed>  $data  Additional data to associate with the step progress.
     * @param  Model|null  $actor  The actor model.
     * @param  bool  $force  Whether to force skipping of the step.
     * @return PassageStepProgress The step progress instance.
     */
    public function skipStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
        bool $force = false,
    ): PassageStepProgress {
        // Get or enroll the subject in the passage, retrieve the step progress and definition
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        // Validate that the prerequisites for the step are satisfied unless forcing skipping
        if ($definition->isRequired() && ! $force) {
            throw StepCannotBeSkipped::required($step);
        }

        // Validate that the prerequisites for the step are satisfied unless forcing skipping
        $progress->forceFill([
            'state' => StepState::Skipped,
            'skipped_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        // Update the enrollment state to indicate that it is in progress and record the last activity time
        $enrollment->forceFill(['last_activity_at' => now()])->save();
        $this->audits->record($enrollment, $force ? 'step.skip_overridden' : 'step.skipped', $step, $actor, $data);
        $this->events->dispatch(new StepSkipped($enrollment, $progress, $actor, $force));
        $this->recalculate($enrollment);

        // Return the refreshed step progress instance
        return $progress->refresh();
    }

    /**
     * Fail a step for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  string  $step  The key of the step to fail.
     * @param  string  $reason  The reason for the failure.
     * @param  array<string, mixed>  $data  Additional data to associate with the step progress.
     * @param  Model|null  $actor  The actor model.
     * @return PassageStepProgress The step progress instance.
     */
    public function failStep(
        Model $subject,
        string $passage,
        string $step,
        string $reason,
        array $data = [],
        ?Model $actor = null,
    ): PassageStepProgress {
        // Get or enroll the subject in the passage, retrieve the step progress and definition
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        // Validate that the prerequisites for the step are satisfied and that retry is available
        $this->assertRetryAvailable($progress, $definition);

        // If the step is already failed, return the progress without making changes
        $progress->forceFill([
            'state' => StepState::Failed,
            'attempts' => max(1, $progress->attempts),
            'failed_at' => now(),
            'failure_reason' => $reason,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        // Update the enrollment state to indicate that it is blocked and record the last activity time
        $enrollment->forceFill([
            'state' => EnrollmentState::Blocked,
            'last_activity_at' => now(),
        ])->save();

        // Record the step failure event in the audit log and dispatch the StepFailed event
        $this->audits->record($enrollment, 'step.failed', $step, $actor, ['reason' => $reason, ...$data]);
        $this->events->dispatch(new StepFailed($enrollment, $progress, $actor, $reason));

        // Recalculate the enrollment state and return the refreshed step progress instance
        return $progress->refresh();
    }

    /**
     * Synchronize the enrollment state for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return PassageEnrollment The synchronized passage enrollment.
     */
    public function sync(Model $subject, string $passage): PassageEnrollment
    {
        // Get or enroll the subject in the passage, retrieve the passage definition, and repair the enrollment if necessary
        $enrollment = $this->getOrEnroll($subject, $passage);
        $definition = $this->definition($passage);
        $this->repairEnrollment($enrollment);
        $enrollment->load('steps');

        // Iterate through each step in the passage definition to evaluate visibility and completion conditions
        foreach ($definition->steps() as $stepDefinition) {
            $progress = $this->stepProgress($enrollment, $stepDefinition->key());

            // If the step is already satisfied (completed or skipped), continue to the next step
            if ($progress->isSatisfied()) {
                continue;
            }

            // Evaluate the visibility condition for the step and skip it if not visible and not required
            $visible = $this->conditions->evaluate(
                $stepDefinition->visibilityCondition(),
                $subject,
                $enrollment,
                $progress,
                true,
            );

            // If the step is not visible and not required, skip it automatically
            if (! $visible && ! $stepDefinition->isRequired()) {
                $this->skipStep($subject, $passage, $stepDefinition->key(), ['automatic' => true]);

                continue;
            }

            // Evaluate the completion condition for the step and complete it if satisfied and prerequisites are met
            $complete = $this->conditions->evaluate(
                $stepDefinition->completionCondition(),
                $subject,
                $enrollment,
                $progress,
                false,
            );

            // If the step is complete and prerequisites are satisfied, complete the step automatically
            if ($complete && $this->prerequisitesSatisfied($enrollment, $stepDefinition)) {
                $this->completeStep($subject, $passage, $stepDefinition->key(), ['automatic' => true]);
            }
        }

        // Recalculate the enrollment state and return the refreshed enrollment instance
        return $this->recalculate($enrollment->refresh());
    }

    /**
     * Get the next step for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return PassageStepProgress|null The next step progress instance or null if none exists.
     */
    public function nextStep(Model $subject, string $passage): ?PassageStepProgress
    {
        // Get or enroll the subject in the passage, retrieve the passage definition, and load the step progress
        $enrollment = $this->getOrEnroll($subject, $passage);
        $definition = $this->definition($passage);
        $enrollment->load('steps');

        // Iterate through each step in the passage definition to find the next step that is visible and has prerequisites satisfied
        foreach ($definition->steps() as $stepDefinition) {
            $progress = $enrollment->steps->firstWhere('step_key', $stepDefinition->key());

            // If the step progress is not found or the step is already satisfied (completed or skipped), continue to the next step
            if (! $progress instanceof PassageStepProgress || $progress->isSatisfied()) {
                continue;
            }

            // Evaluate the visibility condition for the step and check if prerequisites are satisfied
            $visible = $this->conditions->evaluate(
                $stepDefinition->visibilityCondition(),
                $subject,
                $enrollment,
                $progress,
                true,
            );

            // If the step is visible and prerequisites are satisfied, return the step progress as the next step
            if ($visible && $this->prerequisitesSatisfied($enrollment, $stepDefinition)) {
                return $progress;
            }
        }

        // If no next step is found, return null
        return null;
    }

    /**
     * Get the progress snapshot for a subject in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @return ProgressSnapshot The progress snapshot instance.
     */
    public function progress(Model $subject, string $passage): ProgressSnapshot
    {
        // Get or enroll the subject in the passage, load the step progress, and calculate the required and satisfied steps
        $enrollment = $this->getOrEnroll($subject, $passage);
        $enrollment->load('steps');

        // Calculate the required steps, completed required steps, satisfied steps, and progress percentage
        $required = $enrollment->steps->where('required', true);
        $requiredCompleted = $required->filter(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        )->count();
        $satisfied = $enrollment->steps->filter(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        )->count();

        // Calculate the denominator and numerator for the progress percentage, handling cases where there are no required steps
        $denominator = $required->count() > 0 ? $required->count() : $enrollment->steps->count();
        $numerator = $required->count() > 0 ? $requiredCompleted : $satisfied;
        $percentage = $denominator === 0 ? 100 : (int) floor(($numerator / $denominator) * 100);

        // Return a new ProgressSnapshot instance with the calculated values and the next step key
        return new ProgressSnapshot(
            passage: $enrollment->passage_key,
            version: $enrollment->passage_version,
            cycle: $enrollment->cycle,
            state: $enrollment->state,
            requiredTotal: $required->count(),
            requiredCompleted: $requiredCompleted,
            total: $enrollment->steps->count(),
            satisfied: $satisfied,
            percentage: $percentage,
            nextStep: $this->nextStep($subject, $passage)?->step_key,
        );
    }

    /**
     * Cancel a subject's enrollment in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  Model|null  $actor  The actor performing the cancellation.
     * @param  string|null  $reason  The reason for cancellation.
     * @return PassageEnrollment The updated passage enrollment instance.
     */
    public function cancel(Model $subject, string $passage, ?Model $actor = null, ?string $reason = null): PassageEnrollment
    {
        // Get or enroll the subject in the passage and update the enrollment state to cancelled
        $enrollment = $this->getOrEnroll($subject, $passage);
        $enrollment->forceFill([
            'state' => EnrollmentState::Cancelled,
            'cancelled_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        // Record the cancellation event in the audit log and dispatch the PassageCancelled event
        $this->audits->record($enrollment, 'passage.cancelled', actor: $actor, data: ['reason' => $reason]);
        $this->events->dispatch(new PassageCancelled($enrollment, $actor, $reason));

        // Recalculate the enrollment state and return the refreshed enrollment instance
        return $enrollment->refresh();
    }

    /**
     * Restart a subject's enrollment in a passage.
     *
     * @param  Model  $subject  The subject model.
     * @param  string  $passage  The key of the passage.
     * @param  array<string, mixed>  $metadata  Additional metadata for the enrollment.
     * @param  Model|null  $actor  The actor performing the restart.
     * @return PassageEnrollment The updated passage enrollment instance.
     */
    public function restart(
        Model $subject,
        string $passage,
        array $metadata = [],
        ?Model $actor = null,
    ): PassageEnrollment {
        // Get the current enrollment for the subject in the passage
        $previous = $this->current($subject, $passage);

        // If there is a previous enrollment and it is not terminal, cancel it before restarting
        if ($previous !== null && ! $previous->isTerminal()) {
            $this->cancel($subject, $passage, $actor, 'restarted');
        }

        // Enroll the subject in the passage again, creating a new enrollment, and record the restart event in the audit log
        $enrollment = $this->enroll($subject, $passage, $metadata, true, $actor);
        $this->audits->record($enrollment, 'passage.restarted', actor: $actor, data: [
            'previous_enrollment_id' => $previous?->getKey(),
        ]);
        $this->events->dispatch(new PassageRestarted($enrollment, $previous, $actor));

        // Return the enrollment instance
        return $enrollment;
    }

    /**
     * Expire a subject's enrollment in a passage.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to expire.
     * @return PassageEnrollment The updated passage enrollment instance.
     */
    public function expireEnrollment(PassageEnrollment $enrollment): PassageEnrollment
    {
        // If the enrollment is already in a terminal state, return it without making changes
        if ($enrollment->isTerminal()) {
            return $enrollment;
        }

        // Update the enrollment state to expired and record the expiration time
        $enrollment->forceFill([
            'state' => EnrollmentState::Expired,
            'expired_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        // Record the expiration event in the audit log and dispatch the PassageExpired event
        $this->audits->record($enrollment, 'passage.expired');
        $this->events->dispatch(new PassageExpired($enrollment));

        // Recalculate the enrollment state and return the refreshed enrollment instance
        return $enrollment->refresh();
    }

    /**
     * Repair a subject's enrollment in a passage by creating any missing step progress records.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to repair.
     * @return int The number of created step progress records.
     */
    public function repairEnrollment(PassageEnrollment $enrollment): int
    {
        // Get the passage definition for the enrollment and initialize a counter for created step progress records
        $definition = $this->definition($enrollment->passage_key);
        $created = 0;

        // Iterate through each step in the passage definition and check if a corresponding step progress record exists
        foreach ($definition->steps() as $step) {
            $exists = $enrollment->steps()
                ->where('step_key', $step->key())
                ->exists();

            // If the step progress record does not exist, create it and increment the created count
            if (! $exists) {
                $this->createStepProgress($enrollment, $step);
                $created++;
            }
        }

        // If any step progress records were created, record the repair event in the audit log
        if ($created > 0) {
            $this->audits->record($enrollment, 'passage.repaired', data: ['created_steps' => $created]);
        }

        // Recalculate the enrollment state after repairing the enrollment
        return $created;
    }

    /**
     * Create a new step progress record for a given enrollment and step definition.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to associate with the step progress.
     * @param  StepDefinition  $step  The step definition to create progress for.
     * @return PassageStepProgress The created step progress instance.
     */
    private function createStepProgress(PassageEnrollment $enrollment, StepDefinition $step): PassageStepProgress
    {
        /** @var class-string<PassageStepProgress> $model */
        $model = (string) config('passage.models.step', PassageStepProgress::class);

        // Create a new step progress record in the database with the provided enrollment and step definition
        return $model::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'step_key' => $step->key(),
            'position' => $step->position(),
            'required' => $step->isRequired(),
            'state' => StepState::Pending,
            'attempts' => 0,
            'data' => $step->meta(),
            'due_at' => $step->dueMinutes() !== null
                ? now()->addMinutes($step->dueMinutes())
                : null,
        ]);
    }

    /**
     * Get the step progress for a given enrollment and step key, creating it if it does not exist.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to retrieve progress for.
     * @param  string  $step  The key of the step to retrieve progress for.
     * @return PassageStepProgress The step progress instance.
     */
    private function stepProgress(PassageEnrollment $enrollment, string $step): PassageStepProgress
    {
        // Retrieve the step definition for the given enrollment and step key
        $this->definition($enrollment->passage_key)->stepDefinition($step);

        /** @var PassageStepProgress $progress */
        $progress = $enrollment->steps()->firstOrCreate(
            ['step_key' => $step],
            [
                'position' => $this->definition($enrollment->passage_key)->stepDefinition($step)->position(),
                'required' => $this->definition($enrollment->passage_key)->stepDefinition($step)->isRequired(),
                'state' => StepState::Pending,
                'attempts' => 0,
            ],
        );

        // If the step progress was newly created, record the creation event in the audit log
        return $progress;
    }

    /**
     * Recalculate the enrollment state based on the current step progress.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to recalculate.
     * @return PassageEnrollment The updated passage enrollment instance.
     */
    private function recalculate(PassageEnrollment $enrollment): PassageEnrollment
    {
        // If the enrollment is already in a terminal state, return it without making changes
        if ($enrollment->isTerminal()) {
            return $enrollment;
        }

        // Load the step progress for the enrollment and determine if all required steps are satisfied
        $enrollment->load('steps');
        $required = $enrollment->steps->where('required', true);
        $complete = $required->every(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        );

        // Update the enrollment state to completed if all required steps are satisfied, otherwise
        // set it to blocked or in progress based on the step states
        if ($complete) {
            $enrollment->forceFill([
                'state' => EnrollmentState::Completed,
                'completed_at' => now(),
                'last_activity_at' => now(),
            ])->save();

            // Record the passage completion event in the audit log and dispatch the PassageCompleted event
            $this->audits->record($enrollment, 'passage.completed');
            $this->events->dispatch(new PassageCompleted($enrollment));
        } else {
            // Determine if any required steps have failed and update the enrollment state accordingly
            $hasFailedRequired = $required->contains(
                static fn (PassageStepProgress $step): bool => $step->state === StepState::Failed,
            );

            // Update the enrollment state to blocked if any required steps have failed, otherwise set it to in progress
            $enrollment->forceFill([
                'state' => $hasFailedRequired ? EnrollmentState::Blocked : EnrollmentState::InProgress,
            ])->save();
        }

        // Recalculate the enrollment state and return the refreshed enrollment instance
        return $enrollment->refresh();
    }

    /**
     * Assert that the prerequisites for a step are satisfied for a given enrollment.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to check prerequisites for.
     * @param  StepDefinition  $step  The step definition to check prerequisites for.
     *
     * @throws StepBlocked If the prerequisites are not satisfied.
     */
    private function assertPrerequisitesSatisfied(PassageEnrollment $enrollment, StepDefinition $step): void
    {
        // Check if the prerequisites for the step are satisfied and throw an exception if not
        $missing = $this->missingPrerequisites($enrollment, $step);

        // If there are any missing prerequisites, throw a StepBlocked exception with the step key and missing prerequisites
        if ($missing !== []) {
            throw StepBlocked::byPrerequisites($step->key(), $missing);
        }
    }

    /**
     * Check if the prerequisites for a step are satisfied for a given enrollment.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to check prerequisites for.
     * @param  StepDefinition  $step  The step definition to check prerequisites for.
     * @return bool True if the prerequisites are satisfied, false otherwise.
     */
    private function prerequisitesSatisfied(PassageEnrollment $enrollment, StepDefinition $step): bool
    {
        // Check if the prerequisites for the step are satisfied by verifying that there are no missing prerequisites
        return $this->missingPrerequisites($enrollment, $step) === [];
    }

    /**
     * Get the missing prerequisites for a step in a given enrollment.
     *
     * @param  PassageEnrollment  $enrollment  The passage enrollment to check prerequisites for.
     * @param  StepDefinition  $step  The step definition to check prerequisites for.
     * @return array<string> An array of missing prerequisite step keys.
     */
    private function missingPrerequisites(PassageEnrollment $enrollment, StepDefinition $step): array
    {
        // If the step has no prerequisites, return an empty array
        if ($step->prerequisites() === []) {
            return [];
        }

        // Retrieve the states of the prerequisite steps for the enrollment and filter out any that are not satisfied
        $states = $enrollment->steps()
            ->whereIn('step_key', $step->prerequisites())
            ->pluck('state', 'step_key');

        // Return an array of missing prerequisite step keys that are not satisfied
        return array_values(array_filter(
            $step->prerequisites(),
            static function (string $key) use ($states): bool {
                $state = $states->get($key);

                // If the state is not a string or the step state is not satisfied, consider it a missing prerequisite
                return ! is_string($state) || ! StepState::from($state)->isSatisfied();
            },
        ));
    }

    /**
     * Assert that retry is available for a step based on its progress and definition.
     *
     * @param  PassageStepProgress  $progress  The step progress to check retry availability for.
     * @param  StepDefinition  $definition  The step definition to check retry availability for.
     *
     * @throws StepRetryLimitReached If the retry limit has been reached for the step.
     */
    private function assertRetryAvailable(PassageStepProgress $progress, StepDefinition $definition): void
    {
        // If there have been no attempts, retry is available
        if ($progress->attempts === 0) {
            return;
        }

        // If the step does not allow retry and the progress state is failed, throw a StepRetryLimitReached exception
        if (! $definition->allowsRetry() && $progress->state === StepState::Failed) {
            throw StepRetryLimitReached::for($progress->step_key);
        }

        // If the step has a maximum number of attempts and the progress state is failed, throw a StepRetryLimitReached exception
        $maximum = $definition->maxAttempts();
        if ($maximum !== null && $progress->attempts >= $maximum && $progress->state === StepState::Failed) {
            throw StepRetryLimitReached::for($progress->step_key);
        }
    }
}
