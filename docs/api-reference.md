# 🧩 API Reference

This reference focuses on the stable, documented surface shown by the package README. Consult your installed version's source and IDE signatures for optional parameters and return types.

## 👤 `HasPassages` trait

```php
use EloquentWorks\Passage\Traits\HasPassages;
```

### `passageEnrollments()`

Returns the subject's polymorphic enrollment relationship.

```php
$user->passageEnrollments();
```

### `startPassage()`

Starts or resolves an enrollment for a passage.

```php
$user->startPassage(
    'account-setup',
    ['source' => 'registration'],
);
```

### `passageProgress()`

Returns the passage's progress snapshot.

```php
$progress = $user->passageProgress('account-setup');
```

Common properties:

```php
$progress->percentage;
$progress->nextStep;
$progress->state;
```

### `completePassageStep()`

Completes a step after validating prerequisites.

```php
$user->completePassageStep(
    'account-setup',
    'verify-email',
);
```

### `skipPassageStep()`

Skips an eligible optional step.

```php
$user->skipPassageStep(
    'account-setup',
    'product-tour',
);
```

### `failPassageStep()`

Marks a step as failed with a reason.

```php
$user->failPassageStep(
    'account-setup',
    'complete-profile',
    'Validation failed',
);
```

### `restartPassage()`

Creates a new cycle for a repeatable passage.

```php
$user->restartPassage('annual-review');
```

## 🚪 `Passage` facade

```php
use EloquentWorks\Passage\Facades\Passage;
```

### `define()`

Creates or retrieves a passage definition:

```php
Passage::define('account-setup');
```

### `sync()`

Evaluates automatic completion conditions:

```php
Passage::sync($user, 'account-setup');
```

### `completeStep()`

Manager-level step completion, including actor attribution and force override:

```php
Passage::completeStep(
    subject: $user,
    passage: 'account-setup',
    step: 'complete-profile',
    actor: $administrator,
    force: true,
);
```

## 🗺️ `PassageDefinition`

Confirmed fluent methods include:

```php
Passage::define('account-setup')
    ->name('Account setup')
    ->description('Complete setup.')
    ->category('onboarding')
    ->version(1)
    ->dueAfterMinutes(10080)
    ->tags('onboarding', 'account')
    ->step('verify-email', $callback);
```

## 🪜 `StepDefinition`

Confirmed fluent methods include:

```php
$step
    ->name('Verify your email')
    ->route('verification.notice')
    ->completeWhen(EmailIsVerified::class)
    ->dependsOn('previous-step')
    ->optional();
```

## 🛡️ Middleware aliases

```text
passage.complete:{passage}
passage.step:{passage},{step}
passage.next:{passage}
```

## ⏱️ Commands

```text
passage:install
passage:sync
passage:expire
passage:remind
passage:repair
passage:prune
```

Use Artisan for exact options:

```bash
php artisan help passage:repair
```

## 🧱 Models

Default models:

```text
EloquentWorks\Passage\Models\PassageEnrollment
EloquentWorks\Passage\Models\PassageStepProgress
EloquentWorks\Passage\Models\PassageAudit
```

## 📜 Contracts

Automatic class-based conditions implement:

```text
EloquentWorks\Passage\Contracts\StepCondition
```

## ⚠️ Exceptions

Workflow validation uses package exceptions such as:

```text
EloquentWorks\Passage\Exceptions\StepBlocked
EloquentWorks\Passage\Exceptions\StepCannotBeSkipped
```

Catch narrow exceptions only when the application can provide a meaningful recovery path. Otherwise, let the normal exception handler report the failure.
