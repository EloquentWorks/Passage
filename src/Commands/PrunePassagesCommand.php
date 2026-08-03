<?php

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageAudit;
use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Console\Command;

final class PrunePassagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:prune {--days=} {--audit-days=} {--force : Run even when pruning is disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old terminal passages and audit history.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if pruning is enabled in the configuration or if the --force option is provided
        if (! (bool) config('passage.pruning.enabled', false) && ! (bool) $this->option('force')) {
            $this->components->warn('Passage pruning is disabled. Use --force or enable it in configuration.');

            // Exit with a success code to indicate that the command completed without errors, even though no pruning was performed
            return self::SUCCESS;
        }

        // Get the number of days to retain terminal enrollments and audit records from the command options or configuration
        $days = (int) ($this->option('days') ?: config('passage.pruning.retention_days', 365));
        $auditDays = (int) ($this->option('audit-days') ?: config('passage.pruning.audit_retention_days', 730));

        /** @var class-string<PassageEnrollment> $enrollmentModel */
        $enrollmentModel = (string) config('passage.models.enrollment', PassageEnrollment::class);

        /** @var class-string<PassageAudit> $auditModel */
        $auditModel = (string) config('passage.models.audit', PassageAudit::class);

        // Delete audit records older than the specified number of days
        $audits = $auditModel::query()->where('occurred_at', '<', now()->subDays($auditDays))->delete();
        $enrollments = $enrollmentModel::query()
            ->whereIn('state', [EnrollmentState::Completed->value, EnrollmentState::Expired->value, EnrollmentState::Cancelled->value])
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        // Output the number of enrollments and audit records that were pruned
        $this->components->info("Pruned {$enrollments} enrollment(s) and {$audits} audit record(s).");

        // Exit with a success code to indicate that the command completed successfully
        return self::SUCCESS;
    }
}
