<?php

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;

final class RepairPassagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:repair {passage? : Only repair a specific passage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add missing step progress rows to active passage enrollments.';

    /**
     * Execute the console command.
     */
    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $query = $model::query()->whereIn('state', [
            EnrollmentState::Pending->value,
            EnrollmentState::InProgress->value,
            EnrollmentState::Blocked->value,
        ]);

        // If a specific passage is provided as an argument, filter the query to only include enrollments for that passage.
        $passage = $this->argument('passage');
        if (is_string($passage) && $passage !== '') {
            $query->where('passage_key', $passage);
        }

        // Count the number of enrollments that will be processed and display it to the user.
        $created = 0;
        $query->chunkById(100, function ($enrollments) use ($manager, &$created): void {
            foreach ($enrollments as $enrollment) {
                $created += $manager->repairEnrollment($enrollment);
            }
        });

        // Display the total number of missing step rows that were created during the repair process.
        $this->components->info("Created {$created} missing step row(s).");

        // Return a success status code to indicate that the command executed successfully.
        return self::SUCCESS;
    }
}
