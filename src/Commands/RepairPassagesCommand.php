<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;

final class RepairPassagesCommand extends Command
{
    protected $signature = 'passage:repair {passage? : Only repair a specific passage}';

    protected $description = 'Add missing step progress rows to active passage enrollments.';

    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $query = $model::query()->whereIn('state', [
            EnrollmentState::Pending->value,
            EnrollmentState::InProgress->value,
            EnrollmentState::Blocked->value,
        ]);

        $passage = $this->argument('passage');
        if (is_string($passage) && $passage !== '') {
            $query->where('passage_key', $passage);
        }

        $created = 0;
        $query->chunkById(100, function ($enrollments) use ($manager, &$created): void {
            foreach ($enrollments as $enrollment) {
                $created += $manager->repairEnrollment($enrollment);
            }
        });

        $this->components->info("Created {$created} missing step row(s).");

        return self::SUCCESS;
    }
}
