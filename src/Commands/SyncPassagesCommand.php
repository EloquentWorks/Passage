<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

final class SyncPassagesCommand extends Command
{
    protected $signature = 'passage:sync {passage? : Only synchronize a specific passage}';

    protected $description = 'Evaluate automatic completion and visibility conditions for active passages.';

    public function handle(PassageManager $manager): int
    {
        /** @var class-string<PassageEnrollment> $model */
        $model = (string) config('passage.models.enrollment', PassageEnrollment::class);
        $query = $model::query()
            ->with('subject')
            ->whereIn('state', [EnrollmentState::Pending->value, EnrollmentState::InProgress->value, EnrollmentState::Blocked->value]);

        $passage = $this->argument('passage');
        if (is_string($passage) && $passage !== '') {
            $query->where('passage_key', $passage);
        }

        $count = 0;
        $query->chunkById(100, function ($enrollments) use ($manager, &$count): void {
            foreach ($enrollments as $enrollment) {
                $subject = $enrollment->subject;
                if (! $subject instanceof Model) {
                    continue;
                }

                $manager->sync($subject, $enrollment->passage_key);
                $count++;
            }
        });

        $this->components->info("Synchronized {$count} passage enrollment(s).");

        return self::SUCCESS;
    }
}
