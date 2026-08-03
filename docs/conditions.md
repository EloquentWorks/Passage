# 🤖 Conditions and Automation

Conditions let Passage evaluate application state and automatically complete eligible steps.

## 🧩 Create a condition class

A class-based condition implements `StepCondition`:

```php
namespace App\Passage\Conditions;

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

Attach it to a step:

```php
$step->completeWhen(EmailIsVerified::class);
```

## 🧠 Closure conditions

Fluent definitions registered in code can use closures:

```php
$step->completeWhen(
    fn (User $user): bool => $user->profile?->isComplete() === true,
);
```

Use closures for small, local rules. Prefer classes when the rule is reused, needs dependencies, or deserves focused tests.

## 🔄 Run synchronization

Synchronize one subject and passage:

```php
Passage::sync($user, 'account-setup');
```

Synchronize operationally through Artisan:

```bash
php artisan passage:sync
```

A common schedule is:

```php
Schedule::command('passage:sync')->hourly();
```

Applications with immediate domain events should also synchronize when the relevant event occurs instead of waiting for the scheduler.

## 🔗 Prerequisites still apply

An automatic condition does not erase the workflow graph.

If a condition is true but the step's prerequisites are not satisfied, the step remains blocked until the dependencies are satisfied. A later synchronization can then complete it.

## 👁️ Visibility conditions

Passage supports conditional step visibility.

Use visibility for tasks that only apply to some subjects, such as:

- business-only verification;
- region-specific disclosures;
- premium onboarding;
- optional feature setup;
- remediation steps after a failed check.

Visibility and completion are different concerns:

- visibility decides whether a step currently applies;
- completion decides whether an applicable task has been satisfied.

Keep visibility rules stable and deterministic. A step that repeatedly appears and disappears can confuse progress reporting and users.

## 📐 Condition design guidelines

A good condition should:

- be deterministic for the same persisted state;
- avoid mutating unrelated application state;
- avoid network calls in the request path;
- avoid expensive unindexed queries;
- handle missing relations safely;
- return a boolean;
- be independently testable.

Bad condition behavior includes:

- sending mail;
- charging a payment method;
- changing permissions;
- calling a slow third-party API;
- swallowing domain errors and returning a misleading result.

Perform side effects through domain services or event listeners after a state transition.

## ⚡ Bulk synchronization

Scheduled synchronization may process many enrollments. Design conditions with bulk execution in mind:

- eager-load relations when your integration supports it;
- index columns used by conditions;
- avoid per-subject HTTP calls;
- make external checks asynchronous;
- monitor command duration and failures.

## ✅ Testing a condition

```php
public function test_verified_email_satisfies_condition(): void
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $enrollment = $user->startPassage('account-setup');

    Passage::sync($user, 'account-setup');

    $this->assertSame(
        100,
        $user->passageProgress('account-setup')->percentage,
    );
}
```

Also test false, missing-data, and prerequisite-blocked cases.
