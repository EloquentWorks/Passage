# 🩺 Troubleshooting

## 🗄️ `no such table: passage_enrollments`

The package migrations did not run in the active database.

Check:

```bash
php artisan migrate:status
php artisan migrate
```

In tests, ensure the package service provider loads migrations and the test case runs database migrations.

Also confirm the test connection is the connection you expect.

## 🔎 A definition cannot be found

Definitions must be registered before Passage is used.

Common causes:

- registration code is not called from a service provider;
- the service provider is not loaded;
- configuration is cached with stale definitions;
- the passage key differs by spelling or case;
- a test did not register its fixtures.

Clear cached configuration after definition-related config changes:

```bash
php artisan config:clear
```

## 🔗 `StepBlocked` after completing a prerequisite

Check:

1. both steps belong to the same passage;
2. the dependency key exactly matches the prerequisite step key;
3. the prerequisite state was persisted;
4. the current enrollment cycle is being used;
5. the latest package fixes are installed;
6. `passage:repair` has synchronized stored progress.

Inspect:

```php
$progress = $user->passageProgress('account-setup');
$enrollment = $user->passageEnrollments()->latest()->first();
$steps = $enrollment?->steps;
```

Do not compare an enum-cast state as if it were always a raw string in custom integration code.

## 🧭 The next step is unexpected

Passage considers definition order, visibility, current state, required/optional status, and prerequisites.

Verify:

- step order in the definition;
- dependency chains;
- whether an earlier step is hidden;
- whether an optional step remains actionable;
- whether stored progress matches the current definition;
- whether the expected destination is configured.

Run:

```bash
php artisan passage:repair
php artisan passage:sync
```

Then inspect the progress snapshot and audit history.

## 🚧 A passage does not complete

Check that every required, visible step is satisfied.

Optional steps should not block completion, but a step thought to be optional may still be defined as required in the current registered definition.

Also verify that the application is reading the latest enrollment cycle.

## ⏭️ A required step was skipped

Normal APIs should prevent skipping a required step. Look for:

- a forced administrative transition;
- direct model updates;
- stale configuration;
- a definition change after progress was stored;
- custom code bypassing Passage's manager.

Review audit history for actor attribution.

## 🔁 Middleware redirects in a loop

Confirm:

- `incomplete_route` is not protected by the same failing middleware;
- the next step route exists;
- the next step route is accessible before passage completion;
- automatic enrollment is behaving as intended;
- authentication is applied before Passage middleware.

## 🔌 JSON requests return `409`

That is the default incomplete-passage status:

```php
'middleware' => [
    'json_status' => 409,
],
```

Handle the progress payload in the client or change the configured status intentionally.

## 🤖 Automatic conditions do not complete

Check:

- the condition implements `StepCondition`;
- the condition class is registered correctly;
- the subject data is committed before synchronization;
- prerequisites are satisfied;
- `Passage::sync()` or `passage:sync` is running;
- the scheduler is active;
- the condition returns a boolean;
- errors are not hidden in application logs.

## 🔔 Reminders are not sent

Check:

```php
'reminders' => [
    'enabled' => true,
],
```

Then verify:

- `passage:remind` runs;
- the enrollment qualifies within the look-ahead window;
- cooldown has elapsed;
- mail/notification channels are configured;
- the queue worker is running;
- failed jobs and logs are clear.

## 🧹 Pruning does nothing

Pruning is disabled by default:

```php
'pruning' => [
    'enabled' => false,
],
```

Enable it intentionally, verify retention settings, then run:

```bash
php artisan passage:prune --force
```

## 🔄 Definition changes do not appear in stored progress

Run:

```bash
php artisan passage:repair
```

Definition changes do not automatically rewrite every persisted enrollment at code-load time.

Test structural changes on staging before repairing production data.

## 🧪 Static analysis reports configurable relationship generics

When resolving configurable model classes, annotate them as a class string of the expected model type before passing them to `belongsTo()`, `hasMany()`, or `morphMany()`.

Example:

```php
/** @var class-string<PassageEnrollment> $model */
$model = (string) config(
    'passage.models.enrollment',
    PassageEnrollment::class,
);
```

## 🆘 Need more detail

Run command help:

```bash
php artisan help passage:install
php artisan help passage:sync
php artisan help passage:expire
php artisan help passage:remind
php artisan help passage:repair
php artisan help passage:prune
```

Include the Passage version, Laravel version, PHP version, database driver, definition, and a minimal failing test when opening an issue.
