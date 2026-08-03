# 🚪 Laravel Passage Documentation

Passage is a Laravel engine for onboarding flows, checklists, journeys, progression systems, and gated experiences.

It lets an application:

- define ordered, multi-step passages in code or configuration;
- enroll any Eloquent model;
- persist enrollment and step progress;
- enforce prerequisites;
- calculate completion and resolve the next step;
- evaluate automatic completion conditions;
- gate routes with middleware;
- send reminders;
- expire overdue passages;
- repair stored progress after definitions change;
- record lifecycle events and audit history;
- restart repeatable passages as new cycles.

## 📚 Documentation map

### 🚀 Start here

1. [Installation](installation.md)
2. [Core concepts](concepts.md)
3. [Defining passages](definitions.md)
4. [Using Passage](usage.md)
5. [Complete onboarding example](examples.md)

### 🔌 Application integration

- [Conditions and automation](conditions.md)
- [Middleware](middleware.md)
- [Commands and scheduling](commands.md)
- [Events and audit history](events-and-audits.md)
- [Configuration](configuration.md)

### 🧠 Advanced topics

- [Architecture](architecture.md)
- [Customization](customization.md)
- [API reference](api-reference.md)
- [Testing](testing.md)
- [Security](security.md)
- [Troubleshooting](troubleshooting.md)

## 📋 Package requirements

| Requirement | Supported |
|---|---:|
| PHP | `^8.2` |
| Laravel / Illuminate | `^12.0` or `^13.0` |

## 🚀 Quick start

```bash
composer require eloquent-works/passage
php artisan passage:install --migrate
```

Add the trait to an Eloquent model:

```php
use EloquentWorks\Passage\Traits\HasPassages;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    use HasPassages;
}
```

Register a passage during application boot:

```php
use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Facades\Passage;

Passage::define('account-setup')
    ->name('Account setup')
    ->description('Finish the required account setup tasks.')
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
```

Enroll and update a user:

```php
$enrollment = $user->startPassage('account-setup');

$user->completePassageStep('account-setup', 'verify-email');

$progress = $user->passageProgress('account-setup');

$progress->percentage;
$progress->nextStep;
$progress->state;
```

## 🔐 Important boundary

Passage tracks workflow state. It does **not** replace Laravel authorization.

Continue to protect sensitive actions with policies, gates, middleware, ownership checks, and application-specific authorization.
