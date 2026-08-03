<?php

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Events\PassageReminderSent;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Notifications\PassageReminder;
use Illuminate\Console\Command;

final class SendPassageRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:remind {--dry-run : Report matches without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for passage enrollments approaching their due date.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if reminders are enabled in the configuration
        if (! (bool) config('passage.reminders.enabled', true)) {
            $this->components->warn('Passage reminders are disabled.');

            // If the --dry-run option is provided, we still want to report that reminders are disabled
            return self::SUCCESS;
        }

        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $lookAhead = (int) config('passage.reminders.look_ahead_minutes', 1440);
        $cooldown = (int) config('passage.reminders.cooldown_minutes', 1440);
        $count = 0;

        // Query for enrollments that are due within the look-ahead period and have not been reminded within the cooldown period
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
                    // Check if the subject of the enrollment has a notify method (i.e., is not null and can receive notifications)
                    $subject = $enrollment->subject;
                    if (! method_exists($subject, 'notify')) {
                        continue;
                    }

                    // Increment the count of matched reminders
                    $count++;
                    if ((bool) $this->option('dry-run')) {
                        continue;
                    }

                    // Send the reminder notification to the subject and update the last reminded timestamp
                    $subject->notify(new PassageReminder($enrollment));
                    $enrollment->forceFill(['last_reminded_at' => now()])->save();
                    event(new PassageReminderSent($enrollment));
                }
            });

        // Display the total number of reminders that were matched.
        $this->components->info("{$count} reminder(s) matched.");

        // Return a success status code to indicate that the command executed successfully.
        return self::SUCCESS;
    }
}
