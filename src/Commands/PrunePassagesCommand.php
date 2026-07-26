<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use EloquentWorks\Passage\Enums\EnrollmentState;
use EloquentWorks\Passage\Models\PassageAudit;
use EloquentWorks\Passage\Models\PassageEnrollment;
use Illuminate\Console\Command;

final class PrunePassagesCommand extends Command
{
    protected $signature = 'passage:prune {--days=} {--audit-days=} {--force : Run even when pruning is disabled}';

    protected $description = 'Prune old terminal passages and audit history.';

    public function handle(): int
    {
        if (! (bool) config('passage.pruning.enabled', false) && ! (bool) $this->option('force')) {
            $this->components->warn('Passage pruning is disabled. Use --force or enable it in configuration.');

            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?: config('passage.pruning.retention_days', 365));
        $auditDays = (int) ($this->option('audit-days') ?: config('passage.pruning.audit_retention_days', 730));

        /** @var class-string<PassageEnrollment> $enrollmentModel */
        $enrollmentModel = (string) config('passage.models.enrollment', PassageEnrollment::class);
        /** @var class-string<PassageAudit> $auditModel */
        $auditModel = (string) config('passage.models.audit', PassageAudit::class);

        $audits = $auditModel::query()->where('occurred_at', '<', now()->subDays($auditDays))->delete();
        $enrollments = $enrollmentModel::query()
            ->whereIn('state', [EnrollmentState::Completed->value, EnrollmentState::Expired->value, EnrollmentState::Cancelled->value])
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        $this->components->info("Pruned {$enrollments} enrollment(s) and {$audits} audit record(s).");

        return self::SUCCESS;
    }
}
