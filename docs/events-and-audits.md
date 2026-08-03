# 📣 Events and Audit History

Passage exposes lifecycle events and stores audit history for important transitions.

## 📣 Lifecycle events

The package includes events for:

### 🚪 Passage lifecycle

- `PassageEnrolled`
- `PassageCompleted`
- `PassageExpired`
- `PassageCancelled`
- `PassageRestarted`
- `PassageReminderSent`

### 🪜 Step lifecycle

- `StepStarted`
- `StepCompleted`
- `StepSkipped`
- `StepFailed`

## 👂 Listen for an event

Register listeners using Laravel's event system:

```php
namespace App\Listeners;

use EloquentWorks\Passage\Events\PassageCompleted;

final class GrantOnboardingReward
{
    public function handle(PassageCompleted $event): void
    {
        // Dispatch domain work or a queued job.
    }
}
```

Use the exact public properties or accessor methods exposed by the installed event class. Avoid reaching into protected internals.

## 📐 Event listener guidance

Listeners should be:

- idempotent where duplicate delivery would be harmful;
- queued for slow work;
- small and domain-focused;
- covered by tests;
- resilient to a deleted or changed subject where appropriate.

Do not make Passage state transitions depend on a slow external API call in a synchronous listener.

## 🧾 Audit model

The default audit model is:

```php
EloquentWorks\Passage\Models\PassageAudit
```

An audit record is associated with an enrollment and may include:

- an event name;
- a step key;
- an optional actor;
- structured data.

The package configuration allows the audit model and table to be replaced.

## 📖 Read audit history

Audit history is available through an enrollment relationship:

```php
$audits = $enrollment->audits()
    ->latest()
    ->get();
```

A support view can present a timeline:

```php
foreach ($audits as $audit) {
    echo $audit->event;
    echo $audit->step;
    echo $audit->created_at;
}
```

Confirm actual model attributes in the installed version before building a public API around them.

## 👤 Actor attribution

Administrative operations should include the acting model where the API supports it:

```php
Passage::completeStep(
    subject: $user,
    passage: 'account-setup',
    step: 'complete-profile',
    actor: $administrator,
    force: true,
);
```

Actor attribution improves accountability but does not replace authorization or tamper-resistant logging.

## 🔐 Audit data safety

Do not place these values in audit metadata:

- passwords;
- access tokens;
- session IDs;
- private keys;
- full payment details;
- unfiltered request payloads;
- sensitive medical or identity documents.

Prefer identifiers and concise reason codes.

## 🗄️ Retention

Audit retention is configured separately from enrollment retention:

```php
'pruning' => [
    'retention_days' => 365,
    'audit_retention_days' => 730,
],
```

Set retention based on actual business and compliance needs.

## ✅ Testing events

```php
use EloquentWorks\Passage\Events\StepCompleted;
use Illuminate\Support\Facades\Event;

Event::fake();

$user->completePassageStep(
    'account-setup',
    'verify-email',
);

Event::assertDispatched(StepCompleted::class);
```

Also test that events are not dispatched when an operation fails before changing state.
