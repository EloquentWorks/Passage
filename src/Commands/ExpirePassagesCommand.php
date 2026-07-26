<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;

final class ExpirePassagesCommand extends Command
{
    protected $signature = 'passage:expire {--dry-run : Report matches without changing them}';

    protected $description = 'Expire overdue passage enrollments.';

    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $count = 0;

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

        $this->components->info("{$count} passage enrollment(s) matched.");

        return self::SUCCESS;
    }
}
