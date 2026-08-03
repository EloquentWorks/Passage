<?php

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;

final class ExpirePassagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:expire {--dry-run : Report matches without changing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire overdue passage enrollments.';

    /**
     * Execute the console command.
     */
    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $count = 0;

        // We use chunkById to avoid loading all enrollments into memory at once.
        $model::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->whereIn('state', [
                EnrollmentState::Pending->value,
                EnrollmentState::InProgress->value,
                EnrollmentState::Blocked->value,
            ])
            ->chunkById(100, function ($enrollments) use ($manager, &$count): void {
                foreach ($enrollments as $enrollment) {
                    $count++;
                    if (! (bool) $this->option('dry-run')) {
                        $manager->expireEnrollment($enrollment);
                    }
                }
            });

        // Output the number of enrollments that matched the criteria.
        $this->components->info("{$count} passage enrollment(s) matched.");

        // If the dry-run option was used, inform the user that no changes were made.
        return self::SUCCESS;
    }
}
