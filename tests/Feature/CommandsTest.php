<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Tests\Feature;

use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\PassageManager;
use EloquentWorks\Passage\Tests\Fixtures\User;
use EloquentWorks\Passage\Tests\TestCase;

final class CommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(PassageRegistry::class)
            ->define('short')
            ->dueAfterMinutes(1)
            ->step('only');
    }

    public function test_expire_command_expires_overdue_enrollments(): void
    {
        $user = User::query()->create(['name' => 'Nick']);
        $enrollment = app(PassageManager::class)->enroll($user, 'short');
        $enrollment->forceFill(['due_at' => now()->subMinute()])->save();

        $this->artisan('passage:expire')->assertSuccessful();

        self::assertSame(EnrollmentState::Expired, $enrollment->refresh()->state);
    }

    public function test_repair_command_adds_missing_steps(): void
    {
        $user = User::query()->create(['name' => 'Nick']);
        $enrollment = app(PassageManager::class)->enroll($user, 'short');
        $enrollment->steps()->delete();

        $this->artisan('passage:repair')->assertSuccessful();

        self::assertSame(1, $enrollment->steps()->count());
    }
}
