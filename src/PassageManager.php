<?php

declare(strict_types=1);

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
    public function __construct(
        private PassageRegistry $registry,
        private ConditionEvaluator $conditions,
        private AuditLogger $audits,
        private Dispatcher $events,
    ) {}

    public function registry(): PassageRegistry
    {
        return $this->registry;
    }

    public function definition(string $key): PassageDefinition
    {
        return $this->registry->get($key);
    }

    /** @param array<string, mixed> $metadata */
    public function enroll(
        Model $subject,
        string $passage,
        array $metadata = [],
        bool $forceNew = false,
        ?Model $actor = null,
    ): PassageEnrollment {
        $definition = $this->definition($passage);
        $existing = $this->current($subject, $passage);

        if (! $forceNew && $existing !== null) {
            if (! $existing->isTerminal() || ! $definition->isRepeatable()) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($subject, $definition, $metadata, $actor): PassageEnrollment {
            /** @var class-string<PassageEnrollment> $model */
            $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

            $cycle = (int) $model::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', (string) $subject->getKey())
                ->where('passage_key', $definition->key())
                ->max('cycle') + 1;

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

            foreach ($definition->steps() as $step) {
                $this->createStepProgress($enrollment, $step);
            }

            $this->audits->record($enrollment, 'passage.enrolled', actor: $actor);
            $this->events->dispatch(new PassageEnrolled($enrollment, $actor));

            return $this->recalculate($enrollment);
        });
    }

    public function current(Model $subject, string $passage): ?PassageEnrollment
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        return $model::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('passage_key', $passage)
            ->latest('cycle')
            ->first();
    }

    public function active(Model $subject, string $passage): ?PassageEnrollment
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);

        return $model::query()
            ->active()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('passage_key', $passage)
            ->latest('cycle')
            ->first();
    }

    public function getOrEnroll(Model $subject, string $passage): PassageEnrollment
    {
        return $this->current($subject, $passage) ?? $this->enroll($subject, $passage);
    }

    /** @param array<string, mixed> $data */
    public function startStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
    ): PassageStepProgress {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        $this->assertPrerequisitesSatisfied($enrollment, $definition);
        $this->assertRetryAvailable($progress, $definition);

        if ($progress->state === StepState::Completed) {
            return $progress;
        }

        $progress->forceFill([
            'state' => StepState::InProgress,
            'attempts' => $progress->attempts + 1,
            'started_at' => $progress->started_at ?? now(),
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        $enrollment->forceFill([
            'state' => EnrollmentState::InProgress,
            'last_activity_at' => now(),
        ])->save();

        $this->audits->record($enrollment, 'step.started', $step, $actor, $data);
        $this->events->dispatch(new StepStarted($enrollment, $progress, $actor));

        return $progress->refresh();
    }

    /** @param array<string, mixed> $data */
    public function completeStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
        bool $force = false,
    ): PassageStepProgress {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        if (! $force) {
            $this->assertPrerequisitesSatisfied($enrollment, $definition);
        }

        if ($progress->state === StepState::Completed) {
            return $progress;
        }

        $progress->forceFill([
            'state' => StepState::Completed,
            'started_at' => $progress->started_at ?? now(),
            'completed_at' => now(),
            'skipped_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        $enrollment->forceFill(['last_activity_at' => now()])->save();
        $this->audits->record($enrollment, $force ? 'step.overridden' : 'step.completed', $step, $actor, $data);
        $this->events->dispatch(new StepCompleted($enrollment, $progress, $actor, $force));
        $this->recalculate($enrollment);

        return $progress->refresh();
    }

    /** @param array<string, mixed> $data */
    public function skipStep(
        Model $subject,
        string $passage,
        string $step,
        array $data = [],
        ?Model $actor = null,
        bool $force = false,
    ): PassageStepProgress {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        if ($definition->isRequired() && ! $force) {
            throw StepCannotBeSkipped::required($step);
        }

        $progress->forceFill([
            'state' => StepState::Skipped,
            'skipped_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        $enrollment->forceFill(['last_activity_at' => now()])->save();
        $this->audits->record($enrollment, $force ? 'step.skip_overridden' : 'step.skipped', $step, $actor, $data);
        $this->events->dispatch(new StepSkipped($enrollment, $progress, $actor, $force));
        $this->recalculate($enrollment);

        return $progress->refresh();
    }

    /** @param array<string, mixed> $data */
    public function failStep(
        Model $subject,
        string $passage,
        string $step,
        string $reason,
        array $data = [],
        ?Model $actor = null,
    ): PassageStepProgress {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $progress = $this->stepProgress($enrollment, $step);
        $definition = $this->definition($passage)->stepDefinition($step);

        $this->assertRetryAvailable($progress, $definition);

        $progress->forceFill([
            'state' => StepState::Failed,
            'attempts' => max(1, $progress->attempts),
            'failed_at' => now(),
            'failure_reason' => $reason,
            'data' => [...($progress->data ?? []), ...$data],
        ])->save();

        $enrollment->forceFill([
            'state' => EnrollmentState::Blocked,
            'last_activity_at' => now(),
        ])->save();

        $this->audits->record($enrollment, 'step.failed', $step, $actor, ['reason' => $reason, ...$data]);
        $this->events->dispatch(new StepFailed($enrollment, $progress, $actor, $reason));

        return $progress->refresh();
    }

    public function sync(Model $subject, string $passage): PassageEnrollment
    {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $definition = $this->definition($passage);
        $this->repairEnrollment($enrollment);
        $enrollment->load('steps');

        foreach ($definition->steps() as $stepDefinition) {
            $progress = $this->stepProgress($enrollment, $stepDefinition->key());

            if ($progress->isSatisfied()) {
                continue;
            }

            $visible = $this->conditions->evaluate(
                $stepDefinition->visibilityCondition(),
                $subject,
                $enrollment,
                $progress,
                true,
            );

            if (! $visible && ! $stepDefinition->isRequired()) {
                $this->skipStep($subject, $passage, $stepDefinition->key(), ['automatic' => true]);
                continue;
            }

            $complete = $this->conditions->evaluate(
                $stepDefinition->completionCondition(),
                $subject,
                $enrollment,
                $progress,
                false,
            );

            if ($complete && $this->prerequisitesSatisfied($enrollment, $stepDefinition)) {
                $this->completeStep($subject, $passage, $stepDefinition->key(), ['automatic' => true]);
            }
        }

        return $this->recalculate($enrollment->refresh());
    }

    public function nextStep(Model $subject, string $passage): ?PassageStepProgress
    {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $definition = $this->definition($passage);
        $enrollment->load('steps');

        foreach ($definition->steps() as $stepDefinition) {
            $progress = $enrollment->steps->firstWhere('step_key', $stepDefinition->key());

            if (! $progress instanceof PassageStepProgress || $progress->isSatisfied()) {
                continue;
            }

            $visible = $this->conditions->evaluate(
                $stepDefinition->visibilityCondition(),
                $subject,
                $enrollment,
                $progress,
                true,
            );

            if ($visible && $this->prerequisitesSatisfied($enrollment, $stepDefinition)) {
                return $progress;
            }
        }

        return null;
    }

    public function progress(Model $subject, string $passage): ProgressSnapshot
    {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $enrollment->load('steps');

        $required = $enrollment->steps->where('required', true);
        $requiredCompleted = $required->filter(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        )->count();
        $satisfied = $enrollment->steps->filter(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        )->count();
        $denominator = $required->count() > 0 ? $required->count() : $enrollment->steps->count();
        $numerator = $required->count() > 0 ? $requiredCompleted : $satisfied;
        $percentage = $denominator === 0 ? 100 : (int) floor(($numerator / $denominator) * 100);

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

    public function cancel(Model $subject, string $passage, ?Model $actor = null, ?string $reason = null): PassageEnrollment
    {
        $enrollment = $this->getOrEnroll($subject, $passage);
        $enrollment->forceFill([
            'state' => EnrollmentState::Cancelled,
            'cancelled_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        $this->audits->record($enrollment, 'passage.cancelled', actor: $actor, data: ['reason' => $reason]);
        $this->events->dispatch(new PassageCancelled($enrollment, $actor, $reason));

        return $enrollment->refresh();
    }

    /** @param array<string, mixed> $metadata */
    public function restart(
        Model $subject,
        string $passage,
        array $metadata = [],
        ?Model $actor = null,
    ): PassageEnrollment {
        $previous = $this->current($subject, $passage);

        if ($previous !== null && ! $previous->isTerminal()) {
            $this->cancel($subject, $passage, $actor, 'restarted');
        }

        $enrollment = $this->enroll($subject, $passage, $metadata, true, $actor);
        $this->audits->record($enrollment, 'passage.restarted', actor: $actor, data: [
            'previous_enrollment_id' => $previous?->getKey(),
        ]);
        $this->events->dispatch(new PassageRestarted($enrollment, $previous, $actor));

        return $enrollment;
    }

    public function expireEnrollment(PassageEnrollment $enrollment): PassageEnrollment
    {
        if ($enrollment->isTerminal()) {
            return $enrollment;
        }

        $enrollment->forceFill([
            'state' => EnrollmentState::Expired,
            'expired_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        $this->audits->record($enrollment, 'passage.expired');
        $this->events->dispatch(new PassageExpired($enrollment));

        return $enrollment->refresh();
    }

    public function repairEnrollment(PassageEnrollment $enrollment): int
    {
        $definition = $this->definition($enrollment->passage_key);
        $created = 0;

        foreach ($definition->steps() as $step) {
            $exists = $enrollment->steps()
                ->where('step_key', $step->key())
                ->exists();

            if (! $exists) {
                $this->createStepProgress($enrollment, $step);
                $created++;
            }
        }

        if ($created > 0) {
            $this->audits->record($enrollment, 'passage.repaired', data: ['created_steps' => $created]);
        }

        return $created;
    }

    private function createStepProgress(PassageEnrollment $enrollment, StepDefinition $step): PassageStepProgress
    {
        /** @var class-string<PassageStepProgress> $model */
        $model = (string) config('passage.models.step', PassageStepProgress::class);

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

    private function stepProgress(PassageEnrollment $enrollment, string $step): PassageStepProgress
    {
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

        return $progress;
    }

    private function recalculate(PassageEnrollment $enrollment): PassageEnrollment
    {
        if ($enrollment->isTerminal()) {
            return $enrollment;
        }

        $enrollment->load('steps');
        $required = $enrollment->steps->where('required', true);
        $complete = $required->every(
            static fn (PassageStepProgress $step): bool => $step->isSatisfied(),
        );

        if ($complete) {
            $enrollment->forceFill([
                'state' => EnrollmentState::Completed,
                'completed_at' => now(),
                'last_activity_at' => now(),
            ])->save();

            $this->audits->record($enrollment, 'passage.completed');
            $this->events->dispatch(new PassageCompleted($enrollment));
        } else {
            $hasFailedRequired = $required->contains(
                static fn (PassageStepProgress $step): bool => $step->state === StepState::Failed,
            );

            $enrollment->forceFill([
                'state' => $hasFailedRequired ? EnrollmentState::Blocked : EnrollmentState::InProgress,
            ])->save();
        }

        return $enrollment->refresh();
    }

    private function assertPrerequisitesSatisfied(PassageEnrollment $enrollment, StepDefinition $step): void
    {
        $missing = $this->missingPrerequisites($enrollment, $step);

        if ($missing !== []) {
            throw StepBlocked::byPrerequisites($step->key(), $missing);
        }
    }

    private function prerequisitesSatisfied(PassageEnrollment $enrollment, StepDefinition $step): bool
    {
        return $this->missingPrerequisites($enrollment, $step) === [];
    }

    /** @return list<string> */
    private function missingPrerequisites(PassageEnrollment $enrollment, StepDefinition $step): array
    {
        if ($step->prerequisites() === []) {
            return [];
        }

        $states = $enrollment->steps()
            ->whereIn('step_key', $step->prerequisites())
            ->pluck('state', 'step_key');

        return array_values(array_filter(
            $step->prerequisites(),
            static function (string $key) use ($states): bool {
                $state = $states->get($key);

                return ! is_string($state) || ! StepState::from($state)->isSatisfied();
            },
        ));
    }

    private function assertRetryAvailable(PassageStepProgress $progress, StepDefinition $definition): void
    {
        if ($progress->attempts === 0) {
            return;
        }

        if (! $definition->allowsRetry() && $progress->state === StepState::Failed) {
            throw StepRetryLimitReached::for($progress->step_key);
        }

        $maximum = $definition->maxAttempts();
        if ($maximum !== null && $progress->attempts >= $maximum && $progress->state === StepState::Failed) {
            throw StepRetryLimitReached::for($progress->step_key);
        }
    }
}
