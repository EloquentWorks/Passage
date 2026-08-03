# ⚙️ Configuration

Publish the Passage configuration through the install command:

```bash
php artisan passage:install
```

The published file is `config/passage.php`.

## 🗄️ Tables

```php
'tables' => [
    'enrollments' => 'passage_enrollments',
    'steps' => 'passage_step_progress',
    'audits' => 'passage_audits',
],
```

Change table names before the initial migration whenever possible.

If tables already exist, renaming a configuration value alone does not rename the database table. Create a database migration and deploy the configuration change together.

## 🧱 Models

```php
'models' => [
    'enrollment' => PassageEnrollment::class,
    'step' => PassageStepProgress::class,
    'audit' => PassageAudit::class,
],
```

Custom models should extend the corresponding default model unless you intentionally reproduce all required behavior.

```php
namespace App\Models\Passage;

use EloquentWorks\Passage\Models\PassageEnrollment as BaseEnrollment;

final class PassageEnrollment extends BaseEnrollment
{
    // Application-specific relationships or scopes.
}
```

Then update the configuration:

```php
'models' => [
    'enrollment' => App\Models\Passage\PassageEnrollment::class,
    'step' => App\Models\Passage\PassageStepProgress::class,
    'audit' => App\Models\Passage\PassageAudit::class,
],
```

## 🛡️ Middleware

```php
'middleware' => [
    'auto_enroll' => true,
    'incomplete_route' => null,
    'json_status' => 409,
],
```

### `auto_enroll`

When `true`, Passage middleware may enroll a subject when a required passage has not started.

Set it to `false` when enrollment must be initiated by explicit domain logic.

### `incomplete_route`

A fallback named route for incomplete passage handling.

Avoid configuring a route that is itself protected by an incompatible Passage gate.

### `json_status`

HTTP status returned to JSON clients when passage requirements are incomplete.

The default is `409`.

## 🔔 Reminders

```php
'reminders' => [
    'enabled' => true,
    'look_ahead_minutes' => 1440,
    'cooldown_minutes' => 1440,
    'channels' => ['mail'],
],
```

### `enabled`

Globally enables or disables reminder processing.

### `look_ahead_minutes`

How far ahead the reminder command looks for relevant deadlines.

### `cooldown_minutes`

Minimum time between reminders for the same enrollment.

### `channels`

Notification-compatible channels, such as mail, according to the application's notification setup.

## 🧹 Pruning

```php
'pruning' => [
    'enabled' => false,
    'retention_days' => 365,
    'audit_retention_days' => 730,
],
```

Pruning is disabled by default.

### `retention_days`

Retention period for eligible enrollment or progress data.

### `audit_retention_days`

Retention period for eligible audit history.

Review retention with product, support, security, and legal stakeholders before enabling it.

## ⚙️ Configuration-defined passages

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

Configuration-defined conditions must be class strings implementing `StepCondition`.

## ⚡ Configuration caching

After changing production configuration:

```bash
php artisan config:clear
php artisan config:cache
```

Do not use closures in Laravel configuration files because configuration caching cannot serialize them.

## 🌍 Environment-specific values

For operational values, your application may wrap settings with `env()` in the published config:

```php
'reminders' => [
    'enabled' => env('PASSAGE_REMINDERS_ENABLED', true),
    'look_ahead_minutes' => (int) env('PASSAGE_REMINDER_LOOK_AHEAD', 1440),
    'cooldown_minutes' => (int) env('PASSAGE_REMINDER_COOLDOWN', 1440),
    'channels' => ['mail'],
],
```

Only call `env()` from configuration files.
