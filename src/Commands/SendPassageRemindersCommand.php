<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Events\PassageReminderSent;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Notifications\PassageReminder;
use Illuminate\Console\Command;

final class SendPassageRemindersCommand extends Command
{
    protected $signature = 'passage:remind {--dry-run : Report matches without sending notifications}';

    protected $description = 'Send reminders for passage enrollments approaching their due date.';

    public function handle(): int
    {
        if (! (bool) config('passage.reminders.enabled', true)) {
            $this->components->warn('Passage reminders are disabled.');

            return self::SUCCESS;
        }

        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $lookAhead = (int) config('passage.reminders.look_ahead_minutes', 1440);
        $cooldown = (int) config('passage.reminders.cooldown_minutes', 1440);
        $count = 0;

        $model::query()
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addMinutes($lookAhead)])
            ->where(function ($query) use ($cooldown): void {
                $query->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<=', now()->subMinutes($cooldown));
            })
            ->whereIn('state', [EnrollmentState::Pending->value, EnrollmentState::InProgress->value, EnrollmentState::Blocked->value])
            ->with('subject')
            ->chunkById(100, function ($enrollments) use (&$count): void {
                foreach ($enrollments as $enrollment) {
                    $subject = $enrollment->subject;
                    if (! method_exists($subject, 'notify')) {
                        continue;
                    }

                    $count++;
                    if ((bool) $this->option('dry-run')) {
                        continue;
                    }

                    $subject->notify(new PassageReminder($enrollment));
                    $enrollment->forceFill(['last_reminded_at' => now()])->save();
                    event(new PassageReminderSent($enrollment));
                }
            });

        $this->components->info("{$count} reminder(s) matched.");

        return self::SUCCESS;
    }
}
