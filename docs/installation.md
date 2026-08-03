# 🚀 Installation

## 📋 Requirements

Passage currently supports:

- PHP `^8.2`
- Laravel / Illuminate `^12.0 || ^13.0`

Your application also needs a database supported by Laravel.

## 📦 Install with Composer

```bash
composer require eloquent-works/passage
```

Laravel package discovery registers the service provider automatically.

## 🛠️ Install package resources

The shortest installation path is:

```bash
php artisan passage:install --migrate
```

This publishes the package resources and runs the migrations.

To review the generated resources before migrating:

```bash
php artisan passage:install
php artisan migrate
```

Before the first production migration, review the published migrations if your subject models use UUID, ULID, or string primary keys. The package supports integer and UUID/string subjects, but the database column type must match the identifiers used by your application.

## 👤 Add `HasPassages`

Add the trait to each Eloquent model that can participate in a passage:

```php
namespace App\Models;

use EloquentWorks\Passage\Traits\HasPassages;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    use HasPassages;
}
```

The trait exposes the enrollment relationship and convenience methods for starting passages, reading progress, and changing step state.

## 🧭 Define your first passage

Register definitions during application boot, commonly in `AppServiceProvider::boot()`:

```php
namespace App\Providers;

use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Facades\Passage;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Passage::define('account-setup')
            ->name('Account setup')
            ->description('Complete the required account setup.')
            ->category('onboarding')
            ->version(1)
            ->step('verify-email', function (StepDefinition $step): void {
                $step
                    ->name('Verify your email')
                    ->route('verification.notice');
            })
            ->step('complete-profile', function (StepDefinition $step): void {
                $step
                    ->name('Complete your profile')
                    ->route('profile.edit')
                    ->dependsOn('verify-email');
            });
    }
}
```

## ▶️ Start an enrollment

```php
$enrollment = $user->startPassage('account-setup', [
    'source' => 'registration',
]);
```

The second argument is enrollment metadata.

## ✅ Confirm the installation

Check that Passage's commands are registered:

```bash
php artisan list passage
```

Then run your application test suite:

```bash
php artisan test
```

## ⏱️ Queue and scheduler setup

Reminders may use queued mail or database-compatible notification channels. Configure a queue worker when your chosen notification channel requires it.

Add Passage's operational commands to the Laravel scheduler when you use automatic conditions, deadlines, reminders, or pruning:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('passage:sync')->hourly();
Schedule::command('passage:expire')->everyFifteenMinutes();
Schedule::command('passage:remind')->hourly();
Schedule::command('passage:prune')->daily();
```

See [Commands and scheduling](commands.md) for details.

## 🔄 Updating Passage

Before upgrading:

1. read the package changelog;
2. update Composer;
3. publish or review new migrations;
4. run migrations;
5. run `passage:repair` when a release changes stored step synchronization behavior;
6. run your complete test suite.

```bash
composer update eloquent-works/passage
php artisan migrate
php artisan passage:repair
php artisan test
```

Do not overwrite a customized published configuration file without reviewing the differences.
