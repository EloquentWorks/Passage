<?php

declare(strict_types=1);

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
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passage.php', 'passage');

        $this->app->singleton(PassageRegistry::class, function (): PassageRegistry {
            $registry = new PassageRegistry;
            $definitions = config('passage.definitions', []);

            if (is_array($definitions)) {
                $registry->hydrate($definitions);
            }

            return $registry;
        });

        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(PassageManager::class);
        $this->app->alias(PassageManager::class, 'passage');
    }

    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $router->aliasMiddleware('passage.complete', EnsurePassageComplete::class);
        $router->aliasMiddleware('passage.step', EnsurePassageStepComplete::class);
        $router->aliasMiddleware('passage.next', RedirectToNextPassageStep::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/passage.php' => config_path('passage.php'),
            ], 'passage-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'passage-migrations');

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
