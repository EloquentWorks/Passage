<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Tests\Feature;

use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Enums\StepState;
use EloquentWorks\Passage\Events\PassageCompleted;
use EloquentWorks\Passage\Events\PassageEnrolled;
use EloquentWorks\Passage\Events\StepCompleted;
use EloquentWorks\Passage\Exceptions\StepBlocked;
use EloquentWorks\Passage\Exceptions\StepCannotBeSkipped;
use EloquentWorks\Passage\PassageManager;
use EloquentWorks\Passage\Tests\Fixtures\EmailVerifiedCondition;
use EloquentWorks\Passage\Tests\Fixtures\User;
use EloquentWorks\Passage\Tests\TestCase;
use Illuminate\Support\Facades\Event;

final class PassageLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(PassageRegistry::class)
            ->define('account-setup')
            ->name('Account setup')
            ->version(2)
            ->step('verify-email', function (StepDefinition $step): void {
                $step->completeWhen(EmailVerifiedCondition::class);
            })
            ->step('profile', function (StepDefinition $step): void {
                $step->dependsOn('verify-email');
            })
            ->step('tour', function (StepDefinition $step): void {
                $step->optional();
            });
    }

    public function test_it_enrolls_a_subject_and_creates_steps(): void
    {
        Event::fake([PassageEnrolled::class]);
        $user = User::query()->create(['name' => 'Nick']);

        $enrollment = app(PassageManager::class)->enroll($user, 'account-setup');

        self::assertSame(EnrollmentState::InProgress, $enrollment->state);
        self::assertSame(2, $enrollment->passage_version);
        self::assertCount(3, $enrollment->steps()->get());
        Event::assertDispatched(PassageEnrolled::class);
    }

    public function test_prerequisites_block_a_step(): void
    {
        $user = User::query()->create(['name' => 'Nick']);

        $this->expectException(StepBlocked::class);
        app(PassageManager::class)->completeStep($user, 'account-setup', 'profile');
    }

    public function test_required_steps_cannot_be_skipped_without_override(): void
    {
        $user = User::query()->create(['name' => 'Nick']);

        $this->expectException(StepCannotBeSkipped::class);
        app(PassageManager::class)->skipStep($user, 'account-setup', 'verify-email');
    }

    public function test_optional_steps_can_be_skipped(): void
    {
        $user = User::query()->create(['name' => 'Nick']);

        $step = app(PassageManager::class)->skipStep($user, 'account-setup', 'tour');

        self::assertSame(StepState::Skipped, $step->state);
    }

    public function test_passage_completes_when_required_steps_are_satisfied(): void
    {
        Event::fake([StepCompleted::class, PassageCompleted::class]);
        $user = User::query()->create(['name' => 'Nick']);
        $manager = app(PassageManager::class);

        $manager->completeStep($user, 'account-setup', 'verify-email');
        $manager->completeStep($user, 'account-setup', 'profile');

        self::assertSame(EnrollmentState::Completed, $manager->current($user, 'account-setup')?->state);
        Event::assertDispatched(PassageCompleted::class);
    }

    public function test_progress_reports_percentage_and_next_step(): void
    {
        $user = User::query()->create(['name' => 'Nick']);
        $manager = app(PassageManager::class);
        $manager->completeStep($user, 'account-setup', 'verify-email');

        $progress = $manager->progress($user, 'account-setup');

        self::assertSame(50, $progress->percentage);
        self::assertSame('profile', $progress->nextStep);
    }

    public function test_sync_automatically_completes_conditions(): void
    {
        $user = User::query()->create(['name' => 'Nick', 'email_verified' => true]);

        $enrollment = app(PassageManager::class)->sync($user, 'account-setup');

        self::assertSame(
            StepState::Completed,
            $enrollment->steps()->where('step_key', 'verify-email')->firstOrFail()->state,
        );
    }

    public function test_restart_creates_a_new_cycle(): void
    {
        $user = User::query()->create(['name' => 'Nick']);
        $manager = app(PassageManager::class);
        $first = $manager->enroll($user, 'account-setup');
        $second = $manager->restart($user, 'account-setup');

        self::assertSame(1, $first->cycle);
        self::assertSame(2, $second->cycle);
    }
}
