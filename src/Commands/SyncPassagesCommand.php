<?php

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

final class SyncPassagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:sync {passage? : Only synchronize a specific passage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate automatic completion and visibility conditions for active passages.';

    /**
     * Execute the console command.
     */
    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $query = $model::query()
            ->with('subject')
            ->whereIn('state', [EnrollmentState::Pending->value, EnrollmentState::InProgress->value, EnrollmentState::Blocked->value]);

        // If a specific passage is provided, filter the query to only include enrollments for that passage.
        $passage = $this->argument('passage');
        if (is_string($passage) && $passage !== '') {
            $query->where('passage_key', $passage);
        }

        // Count the number of enrollments processed.
        $count = 0;
        $query->chunkById(100, function ($enrollments) use ($manager, &$count): void {
            foreach ($enrollments as $enrollment) {
                // Ensure the subject is a valid model instance.
                $subject = $enrollment->subject;
                if (! $subject instanceof Model) {
                    continue;
                }

                // Synchronize the passage for the subject.
                $manager->sync($subject, $enrollment->passage_key);
                $count++;
            }
        });

        // Output the number of enrollments synchronized.
        $this->components->info("Synchronized {$count} passage enrollment(s).");

        // Return a success status code.
        return self::SUCCESS;
    }
}
