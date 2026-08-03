<?php

namespace EloquentWorks\Passage;

use EloquentWorks\Passage\Commands\ExpirePassagesCommand;
use EloquentWorks\Passage\Commands\InstallPassageCommand;
use EloquentWorks\Passage\Commands\PrunePassagesCommand;
use EloquentWorks\Passage\Commands\RepairPassagesCommand;
use EloquentWorks\Passage\Commands\SendPassageRemindersCommand;
use EloquentWorks\Passage\Commands\SyncPassagesCommand;
use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\Http\Middleware\EnsurePassageComplete;
use EloquentWorks\Passage\Http\Middleware\EnsurePassageStepComplete;
use EloquentWorks\Passage\Http\Middleware\RedirectToNextPassageStep;
use EloquentWorks\Passage\Services\AuditLogger;
use EloquentWorks\Passage\Services\ConditionEvaluator;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class PassageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge the default configuration for Passage into the application's configuration.
        $this->mergeConfigFrom(__DIR__.'/../config/passage.php', 'passage');

        // Register the PassageRegistry as a singleton in the service container.
        $this->app->singleton(PassageRegistry::class, function (): PassageRegistry {
            $registry = new PassageRegistry;
            $definitions = config('passage.definitions', []);

            // If the definitions are an array, hydrate the registry with the definitions.
            if (is_array($definitions)) {
                $registry->hydrate($definitions);
            }

            // Return the PassageRegistry instance.
            return $registry;
        });

        // Register the ConditionEvaluator and AuditLogger as singletons in the service container.
        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(PassageManager::class);
        $this->app->alias(PassageManager::class, 'passage');
    }

    /**
     * Bootstrap any application services.
     *
     * @param  Router  $router  The router instance for registering middleware.
     * @return void
     */
    public function boot(Router $router): void
    {
        // Load the migrations for the Passage package.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware aliases for Passage-related middleware.
        $router->aliasMiddleware('passage.complete', EnsurePassageComplete::class);
        $router->aliasMiddleware('passage.step', EnsurePassageStepComplete::class);
        $router->aliasMiddleware('passage.next', RedirectToNextPassageStep::class);

        // Ensure we are running in the console
        if ($this->app->runningInConsole()) {
            // Publish the configuration file for Passage to the application's config directory.
            $this->publishes([
                __DIR__.'/../config/passage.php' => config_path('passage.php'),
            ], 'passage-config');

            // Publish the migration files for Passage to the application's database/migrations directory.
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'passage-migrations');

            // Register the console commands provided by the Passage package.
            $this->commands([
                InstallPassageCommand::class,
                ExpirePassagesCommand::class,
                SendPassageRemindersCommand::class,
                PrunePassagesCommand::class,
                RepairPassagesCommand::class,
                SyncPassagesCommand::class,
            ]);
        }
    }
}
