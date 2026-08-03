# 🧭 Defining Passages

Passages may be registered fluently in application code or declared in configuration.

## 🧭 Fluent definitions

Register fluent definitions during application boot:

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
            ->name('Take the product tour')
            ->optional()
            ->route('tour.start');
    });
```

## 🔑 Definition keys

Use stable, machine-readable keys:

```text
account-setup
seller-verification
annual-review
incident-response
```

Treat passage and step keys as persisted identifiers. Renaming a key after enrollments exist can make stored progress appear unrelated to the new definition.

Prefer lowercase kebab-case and keep keys independent from translated display names.

## ✍️ Display data

Display names and descriptions may change without changing the key:

```php
Passage::define('seller-verification')
    ->name('Seller verification')
    ->description('Verify your business before listing products.');
```

## 🏷️ Categories and tags

Categories provide one primary organizational value. Tags provide multiple labels.

```php
Passage::define('seller-verification')
    ->category('compliance')
    ->tags('seller', 'verification', 'launch-blocking');
```

Applications can use these values to group passages, build dashboards, or apply internal conventions.

## 🔢 Versions

Assign an integer definition version:

```php
Passage::define('account-setup')->version(2);
```

Increment the version when a structural change requires existing enrollments to be reviewed or repaired.

Examples:

- adding a new required step;
- changing dependency chains;
- changing completion semantics;
- removing or replacing a step.

## ⏰ Passage deadlines

Set a relative deadline in minutes:

```php
Passage::define('account-setup')
    ->dueAfterMinutes(7 * 24 * 60);
```

The enrollment receives a due date based on the configured duration.

Run `passage:expire` on a schedule to transition overdue enrollments.

## ✅ Required steps

Steps are required by default:

```php
->step('verify-email', function (StepDefinition $step): void {
    $step
        ->name('Verify your email')
        ->route('verification.notice');
})
```

Required steps must be satisfied before the passage can complete.

## ⏭️ Optional steps

Mark a step as optional:

```php
->step('product-tour', function (StepDefinition $step): void {
    $step
        ->name('Take the product tour')
        ->optional()
        ->route('tour.start');
})
```

Optional steps can be skipped and do not prevent completion.

## 🔗 Prerequisites

Add dependencies with `dependsOn()`:

```php
->step('complete-profile', function (StepDefinition $step): void {
    $step
        ->name('Complete your profile')
        ->dependsOn('verify-email')
        ->route('profile.edit');
})
```

Keep dependency graphs understandable:

- avoid circular dependencies;
- reference steps in the same passage;
- use prerequisites only for actual requirements;
- test every branch after changing dependencies.

## 🧭 Destinations

Passage supports named-route and direct-URL destinations.

A named route is preferred when the destination belongs to the Laravel application:

```php
$step->route('profile.edit');
```

Named routes survive host and path changes better than hard-coded URLs.

## 🤖 Automatic completion

Associate a condition class:

```php
$step->completeWhen(EmailIsVerified::class);
```

Fluent definitions may also use closures:

```php
$step->completeWhen(
    fn (User $user): bool => $user->profile?->isComplete() === true,
);
```

Use class-based conditions for reusable or testable domain rules. See [Conditions and automation](conditions.md).

## ⚙️ Configuration-defined passages

Definitions can also be placed in `config/passage.php`:

```php
'definitions' => [
    'account-setup' => [
        'name' => 'Account setup',
        'version' => 1,
        'category' => 'onboarding',
        'repeatable' => false,
        'due_after_minutes' => 10080,
        'steps' => [
            'verify-email' => [
                'name' => 'Verify your email',
                'required' => true,
                'route' => 'verification.notice',
            ],
        ],
    ],
],
```

Configuration definitions are useful when:

- the definitions are mostly declarative;
- environments need different passage sets;
- configuration publishing is part of the deployment workflow.

Use class-string conditions for configuration-defined steps. Closures cannot be serialized into a configuration file.

## 🔄 Changing live definitions

Before editing a definition used by existing enrollments:

1. decide whether the change is backward compatible;
2. increment the definition version when the structure changes;
3. determine how existing progress should map to the new definition;
4. deploy the code;
5. run `passage:repair`;
6. inspect affected enrollments and audits;
7. test the next-step and completion behavior.

Never silently reuse an old step key for a task with different meaning.
