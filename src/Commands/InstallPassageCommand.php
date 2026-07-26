<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Commands;

use Illuminate\Console\Command;

final class InstallPassageCommand extends Command
{
    protected $signature = 'passage:install {--migrate : Run pending migrations after publishing}';

    protected $description = 'Publish Passage configuration and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'passage-config']);
        $this->call('vendor:publish', ['--tag' => 'passage-migrations']);

        if ((bool) $this->option('migrate')) {
            $this->call('migrate');
        }

        $this->components->info('Laravel Passage has been installed.');

        return self::SUCCESS;
    }
}
