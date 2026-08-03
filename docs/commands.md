# ⏱️ Commands and Scheduling

Passage includes commands for installation and ongoing workflow maintenance.

Use `php artisan help <command>` to see the options available in the installed version.

## 📦 Install

```bash
php artisan passage:install
```

Install and migrate in one operation:

```bash
php artisan passage:install --migrate
```

Review published migrations before running them in production.

## 🤖 Synchronize automatic conditions

```bash
php artisan passage:sync
```

The command evaluates automatic completion conditions for eligible enrollments.

Recommended schedule:

```php
Schedule::command('passage:sync')->hourly();
```

Use a shorter interval only when conditions are inexpensive and users need near-real-time updates.

## ⏰ Expire overdue passages

```bash
php artisan passage:expire
```

This processes enrollments whose deadlines have passed.

Recommended schedule:

```php
Schedule::command('passage:expire')->everyFifteenMinutes();
```

Expiration is driven by the stored due date. Make sure application and database clocks are configured consistently.

## 🔔 Send reminders

```bash
php artisan passage:remind
```

Reminder behavior is controlled by:

```php
'reminders' => [
    'enabled' => true,
    'look_ahead_minutes' => 1440,
    'cooldown_minutes' => 1440,
    'channels' => ['mail'],
],
```

Recommended schedule:

```php
Schedule::command('passage:remind')->hourly();
```

Configure queues and mail before enabling reminders in production.

## 🛠️ Repair stored progress

```bash
php artisan passage:repair
```

Repair synchronizes persisted enrollment steps with current definitions.

Run it after intentionally changing a live definition, especially when:

- adding steps;
- removing steps;
- changing required or optional status;
- changing prerequisites;
- changing definition versions;
- upgrading a release that changes synchronization behavior.

Back up production data before large definition migrations and review the result on staging first.

## 🧹 Prune retained data

```bash
php artisan passage:prune --force
```

Pruning is disabled by default:

```php
'pruning' => [
    'enabled' => false,
    'retention_days' => 365,
    'audit_retention_days' => 730,
],
```

The `--force` flag is appropriate for deliberate non-interactive execution. Confirm the installed command's help output before adding it to automation.

Recommended schedule when pruning is enabled:

```php
Schedule::command('passage:prune --force')->daily();
```

Retention rules can affect support, analytics, compliance, and legal obligations. Agree on them before deleting production history.

## 📅 Suggested scheduler

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('passage:sync')->hourly();
Schedule::command('passage:expire')->everyFifteenMinutes();
Schedule::command('passage:remind')->hourly();
Schedule::command('passage:prune --force')->daily();
```

## ✅ Deployment checklist

After deploying definition changes:

```bash
php artisan migrate --force
php artisan passage:repair
php artisan passage:sync
```

Then verify:

- command exit codes;
- application logs;
- queue failures;
- reminder delivery;
- a sample enrollment's progress;
- audit events;
- scheduler execution.

## 🖥️ Multi-server deployments

Run scheduled Passage commands through Laravel's normal single-server or locking strategy when duplicate processing would be undesirable.

Typical Laravel scheduler controls include `onOneServer()` and `withoutOverlapping()`. Apply them according to your cache and deployment architecture.
