<?php

namespace EloquentWorks\Passage\Commands;

use Illuminate\Console\Command;

final class InstallPassageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passage:install {--migrate : Run pending migrations after publishing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Passage configuration and migrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Publish the configuration and migration files
        $this->call('vendor:publish', ['--tag' => 'passage-config']);
        $this->call('vendor:publish', ['--tag' => 'passage-migrations']);

        // If the --migrate option is provided, run the migrations
        if ((bool) $this->option('migrate')) {
            $this->call('migrate');
        }

        // Inform the user that the installation is complete
        $this->components->info('Laravel Passage has been installed.');

        // Return a success exit code
        return self::SUCCESS;
    }
}
