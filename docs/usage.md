# 👤 Using Passage

The `HasPassages` trait is the most convenient API for normal application code. The `Passage` facade exposes manager-level operations for services, jobs, commands, and administrative actions.

## ▶️ Start a passage

```php
$enrollment = $user->startPassage('account-setup');
```

Attach enrollment metadata:

```php
$enrollment = $user->startPassage('account-setup', [
    'source' => 'registration',
    'campaign' => 'summer-launch',
]);
```

Starting a non-repeatable passage that already has an active or historical enrollment follows the package's current enrollment resolution rules. Use `restartPassage()` when you explicitly intend to create a new cycle.

## 📊 Read progress

```php
$progress = $user->passageProgress('account-setup');

$progress->percentage;
$progress->nextStep;
$progress->state;
```

Use progress snapshots for:

- onboarding dashboards;
- checklist widgets;
- API responses;
- redirect decisions;
- support tools.

A progress snapshot is derived from the current definition and stored progress. Keep definitions registered anywhere progress is calculated.

## ✅ Complete a step

```php
$user->completePassageStep(
    'account-setup',
    'verify-email',
);
```

Passage checks prerequisites before completing a step.

When all required steps are satisfied, Passage completes the enrollment.

## ⏭️ Skip an optional step

```php
$user->skipPassageStep(
    'account-setup',
    'product-tour',
);
```

Required steps cannot normally be skipped.

## ❌ Fail a step

```php
$user->failPassageStep(
    'account-setup',
    'complete-profile',
    'Profile validation failed.',
);
```

Failure information can support retries, user feedback, and audit review.

Do not store secrets or raw sensitive request payloads in failure messages.

## 🔁 Restart a passage

```php
$user->restartPassage('account-setup');
```

Restarting creates a new cycle for a repeatable flow.

Use restart for recurring workflows such as:

- annual reviews;
- recurring compliance checks;
- seasonal tutorials;
- periodic account verification.

## 🛡️ Administrative override

Manager operations may accept an actor and a force flag:

```php
use EloquentWorks\Passage\Facades\Passage;

Passage::completeStep(
    subject: $user,
    passage: 'account-setup',
    step: 'complete-profile',
    actor: $administrator,
    force: true,
);
```

Use forced transitions sparingly. Always authorize the actor and preserve a useful audit trail.

## 🤖 Synchronize automatic conditions

```php
use EloquentWorks\Passage\Facades\Passage;

Passage::sync($user, 'account-setup');
```

Synchronization evaluates automatic completion conditions and updates eligible progress.

Use it:

- after domain events that may satisfy a condition;
- after a user returns to the passage;
- from a queued job;
- through the scheduled `passage:sync` command.

## 🗂️ Access enrollments

The trait exposes the polymorphic enrollment relationship:

```php
$enrollments = $user->passageEnrollments()
    ->latest()
    ->get();
```

Prefer high-level Passage APIs for state transitions. Directly updating model state can bypass prerequisites, lifecycle events, completion recalculation, and audits.

## 🔌 API responses

A typical controller response:

```php
public function show(User $user): JsonResponse
{
    $this->authorize('viewPassageProgress', $user);

    return response()->json([
        'data' => $user->passageProgress('account-setup'),
    ]);
}
```

Treat passage metadata and audit data as potentially sensitive. Expose only fields required by the client.

## 🏗️ Recommended service boundary

For larger applications, wrap Passage calls in an application service:

```php
final class AccountSetup
{
    public function start(User $user): void
    {
        $user->startPassage('account-setup', [
            'source' => 'registration',
        ]);
    }

    public function emailVerified(User $user): void
    {
        $user->completePassageStep(
            'account-setup',
            'verify-email',
        );
    }
}
```

This keeps controllers thin and makes domain-specific authorization and side effects explicit.
