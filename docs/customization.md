# 🛠️ Customization

Passage is designed to keep workflow definitions in application code while allowing infrastructure models and tables to be replaced.

## 🧱 Custom models

The configurable model classes are:

- enrollment;
- step progress;
- audit.

Extend the default models:

```php
namespace App\Models\Passage;

use EloquentWorks\Passage\Models\PassageEnrollment as BasePassageEnrollment;

final class PassageEnrollment extends BasePassageEnrollment
{
    public function scopeForCategory($query, string $category)
    {
        return $query->where('metadata->category', $category);
    }
}
```

Then update `config/passage.php`.

Preserve inherited casts, relationships, table resolution, and package behavior.

## 🗄️ Custom tables

Change table names in configuration before migration:

```php
'tables' => [
    'enrollments' => 'workflow_enrollments',
    'steps' => 'workflow_step_progress',
    'audits' => 'workflow_audits',
],
```

If the package migrations are already deployed, add your own rename migration.

## 🧩 Custom condition classes

Use `StepCondition` for reusable automatic checks:

```php
final class HasAcceptedTerms implements StepCondition
{
    public function evaluate(
        Model $subject,
        PassageEnrollment $enrollment,
        PassageStepProgress $step,
    ): bool {
        return $subject->terms_accepted_at !== null;
    }
}
```

Condition classes are the preferred extension point for application-specific completion logic.

## 🏗️ Domain services around Passage

Wrap Passage in application services when operations require additional authorization or domain behavior:

```php
final class SellerVerification
{
    public function approve(User $seller, User $moderator): void
    {
        Gate::forUser($moderator)->authorize('approveSeller', $seller);

        Passage::completeStep(
            subject: $seller,
            passage: 'seller-verification',
            step: 'manual-review',
            actor: $moderator,
            force: true,
        );
    }
}
```

This keeps package concerns separate from business rules.

## 👂 Custom listeners

Use lifecycle events to integrate:

- analytics;
- achievements;
- notifications;
- provisioning;
- support timelines;
- application-specific jobs.

Queue expensive work and make listeners idempotent.

## 🔔 Custom notification behavior

The package reminder settings expose notification channels. Configure Laravel mail, database notifications, or application-specific channels as required by your installation.

Keep reminder copy and recipient selection in application code when the message contains product-specific language.

## 🏭 Definition factories

Large applications can organize definitions into dedicated classes:

```php
namespace App\Passage;

use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Facades\Passage;

final class RegisterAccountSetup
{
    public function __invoke(): void
    {
        Passage::define('account-setup')
            ->name('Account setup')
            ->version(1)
            ->step('verify-email', function (StepDefinition $step): void {
                $step
                    ->name('Verify your email')
                    ->route('verification.notice');
            });
    }
}
```

Call the registrar from a service provider.

## 🌍 Localization

Keep stable keys in English-like machine form and localize display text at registration time:

```php
Passage::define('account-setup')
    ->name(__('passages.account_setup.name'))
    ->description(__('passages.account_setup.description'));
```

Do not translate persisted passage or step keys.

## ⚠️ Replacing manager behavior

Prefer composition, conditions, event listeners, custom models, and application services before replacing core manager behavior.

A hard fork of transition logic can break:

- prerequisite enforcement;
- audit history;
- event dispatch;
- completion recalculation;
- repair and synchronization.
