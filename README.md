# 🚪 Laravel Passage

[![Tests](https://github.com/EloquentWorks/Passage/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Passage/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/EloquentWorks/Passage)](https://github.com/EloquentWorks/Passage/releases)
[![License](https://img.shields.io/github/license/EloquentWorks/Passage)](LICENSE)

A feature-rich onboarding, checklist, journey, progression, and gated-experience engine for Laravel.

Passage lets applications define multi-step journeys in code, enroll any Eloquent model, persist step progress, enforce prerequisites, calculate completion, automatically evaluate conditions, redirect users to their next task, send reminders, record audit history, expire overdue journeys, and safely restart repeatable flows.

## ✨ Features

- Fluent and configuration-based passage definitions
- Required and optional steps
- Step prerequisites and dependency chains
- Automatic completion conditions
- Conditional step visibility
- Named-route and direct-URL destinations
- Persistent enrollment and step progress
- Pending, in-progress, blocked, completed, expired, and cancelled enrollment states
- Pending, in-progress, completed, skipped, failed, and blocked step states
- Retry policies and maximum-attempt limits
- Passage and step deadlines
- Repeatable passages with cycle tracking
- Manual completion, administrative overrides, skipping, failure, cancellation, and restart
- Progress percentages and next-step resolution
- Middleware for passage and individual-step gating
- JSON-friendly incomplete responses
- Events for every major lifecycle transition
- Queued mail/database-compatible reminders
- Audit history with optional actor attribution
- Metadata on definitions, enrollments, and steps
- Automatic repair and synchronization commands
- Expiration and retention-pruning commands
- Configurable models and table names
- Integer and UUID/string subject-key support
- Laravel package auto-discovery
- PHPUnit, PHPStan/Larastan, Pint, and GitHub Actions

## 📋 Requirements

| Package | Supported |
|---|---:|
| PHP | `^8.2` |
| Laravel / Illuminate | `^12.0 \|\| ^13.0` |

## 🚀 Installation

```bash
composer require eloquent-works/passage
php artisan passage:install --migrate
```

Add `HasPassages` to any Eloquent model that can participate:

```php
use EloquentWorks\Passage\Traits\HasPassages;

class User extends Authenticatable
{
    use HasPassages;
}
```

## 🧭 Define a Passage

Register passages during application boot, such as in `AppServiceProvider::boot()`:

```php
use App\Passage\Conditions\EmailIsVerified;
use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Facades\Passage;

Passage::define('account-setup')
    ->name('Account setup')
    ->description('Finish the required account setup tasks.')
    ->category('onboarding')
    ->version(1)
    ->dueAfterMinutes(7 * 24 * 60)
    ->tags('onboarding', 'account')
    ->step('verify-email', function (StepDefinition $step): void {
        $step
            ->name('Verify your email')
            ->route('verification.notice')
            ->completeWhen(EmailIsVerified::class);
    })
    ->step('complete-profile', function (StepDefinition $step): void {
        $step
            ->name('Complete your profile')
            ->route('profile.edit')
            ->dependsOn('verify-email');
    })
    ->step('product-tour', function (StepDefinition $step): void {
        $step
            ->optional()
            ->route('tour.start');
    });
```

## 👤 Enroll and Track Progress

```php
$enrollment = $user->startPassage('account-setup', [
    'source' => 'registration',
]);

$progress = $user->passageProgress('account-setup');

$progress->percentage; // 0–100
$progress->nextStep;
$progress->state;
```

## ✅ Complete, Skip, Fail, and Restart

```php
$user->completePassageStep('account-setup', 'verify-email');
$user->skipPassageStep('account-setup', 'product-tour');
$user->failPassageStep('account-setup', 'complete-profile', 'Validation failed');
$user->restartPassage('account-setup');
```

Administrative override:

```php
Passage::completeStep(
    subject: $user,
    passage: 'account-setup',
    step: 'complete-profile',
    actor: $administrator,
    force: true,
);
```

## 🤖 Automatic Completion

Conditions implement `StepCondition`:

```php
use EloquentWorks\Passage\Contracts\StepCondition;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;
use Illuminate\Database\Eloquent\Model;

final class EmailIsVerified implements StepCondition
{
    public function evaluate(
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
    ): bool {
        return method_exists($subject, 'hasVerifiedEmail')
            && $subject->hasVerifiedEmail();
    }
}
```

Then synchronize:

```php
Passage::sync($user, 'account-setup');
```

Closures are also supported for definitions registered in code:

```php
$step->completeWhen(
    fn (User $user): bool => $user->profile?->isComplete() === true,
);
```

## 🛡️ Middleware

Require an entire passage:

```php
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'passage.complete:account-setup']);
```

Require one step:

```php
Route::get('/advanced', AdvancedController::class)
    ->middleware(['auth', 'passage.step:account-setup,verify-email']);
```

Redirect directly to the next configured step:

```php
Route::get('/onboarding', OnboardingController::class)
    ->middleware(['auth', 'passage.next:account-setup']);
```

JSON requests receive a configurable `409` response containing the progress snapshot.

## ⏱️ Operations

```bash
php artisan passage:sync
php artisan passage:expire
php artisan passage:remind
php artisan passage:repair
php artisan passage:prune --force
```

Suggested scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('passage:sync')->hourly();
Schedule::command('passage:expire')->everyFifteenMinutes();
Schedule::command('passage:remind')->hourly();
Schedule::command('passage:prune')->daily();
```

## 📣 Events

- `PassageEnrolled`
- `PassageCompleted`
- `PassageExpired`
- `PassageCancelled`
- `PassageRestarted`
- `PassageReminderSent`
- `StepStarted`
- `StepCompleted`
- `StepSkipped`
- `StepFailed`

## ✅ Quality Checks

```bash
composer validate --strict
composer quality
```

Or separately:

```bash
composer format
composer analyse
composer test
```

## 📚 Documentation

- [Installation](docs/installation.md)
- [Defining Passages](docs/definitions.md)
- [Using Passage](docs/usage.md)
- [Conditions and Automation](docs/conditions.md)
- [Middleware](docs/middleware.md)
- [Commands and Scheduling](docs/commands.md)
- [Events and Audit History](docs/events-and-audits.md)
- [Configuration](docs/configuration.md)
- [Testing](docs/testing.md)
- [Security](docs/security.md)
- [Upgrading](UPGRADING.md)

## 🔐 Security

Passage tracks workflow state; it does not replace Laravel authorization. Continue to authorize protected actions with policies, gates, and application-specific checks. Treat enrollment and step metadata as user-generated data when it contains request input.

Report vulnerabilities privately according to [SECURITY.md](SECURITY.md).

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 📄 License

Laravel Passage is open-source software licensed under the [MIT License](LICENSE).
