# 🚀 Complete Onboarding Example

This example creates an account setup passage with an automatic email check, a profile prerequisite, and an optional product tour.

## 1. Add the trait

```php
namespace App\Models;

use EloquentWorks\Passage\Traits\HasPassages;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    use HasPassages;
}
```

## 2. Create the condition

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

## 3. Register the passage

```php
namespace App\Providers;

use App\Passage\Conditions\EmailIsVerified;
use EloquentWorks\Passage\Definitions\StepDefinition;
use EloquentWorks\Passage\Facades\Passage;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Passage::define('account-setup')
            ->name('Account setup')
            ->description('Complete the required account setup tasks.')
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
    }
}
```

## 4. Start after registration

```php
final class StartAccountSetup
{
    public function handle(User $user): void
    {
        $user->startPassage('account-setup', [
            'source' => 'registration',
        ]);
    }
}
```

## 5. Synchronize after email verification

```php
use EloquentWorks\Passage\Facades\Passage;

Passage::sync($user, 'account-setup');
```

The email step can be automatically completed after the user's verification state is committed.

## 6. Complete the profile step

```php
$user->completePassageStep(
    'account-setup',
    'complete-profile',
);
```

If email verification is not satisfied, Passage throws `StepBlocked`.

## 7. Skip the optional tour

```php
$user->skipPassageStep(
    'account-setup',
    'product-tour',
);
```

## 8. Show progress

```php
final class OnboardingController
{
    public function __invoke(Request $request): View
    {
        return view('onboarding.show', [
            'progress' => $request->user()
                ->passageProgress('account-setup'),
        ]);
    }
}
```

```blade
<div>
    <p>{{ $progress->percentage }}% complete</p>

    @if ($progress->nextStep)
        <a href="{{ $progress->nextStep->url }}">
            Continue onboarding
        </a>
    @endif
</div>
```

Adapt the exact next-step destination property to the progress DTO exposed by the installed package.

## 9. Gate the dashboard

```php
Route::get('/dashboard', DashboardController::class)
    ->middleware([
        'auth',
        'passage.complete:account-setup',
    ]);
```

## 10. Add scheduled operations

```php
Schedule::command('passage:sync')->hourly();
Schedule::command('passage:expire')->everyFifteenMinutes();
Schedule::command('passage:remind')->hourly();
```

## 11. Test the flow

Test at least:

- enrollment creates all steps;
- profile is blocked before email verification;
- sync completes verified email;
- profile can then complete;
- optional tour can be skipped;
- passage reaches 100%;
- dashboard middleware allows the completed user;
- events and audits are recorded.
