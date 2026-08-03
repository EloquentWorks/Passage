# 🛡️ Middleware

Passage registers middleware aliases for gating routes by passage or step progress.

Always place authentication before Passage middleware.

## ✅ Require a completed passage

```php
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)
    ->middleware([
        'auth',
        'passage.complete:account-setup',
    ]);
```

The request is allowed when the authenticated subject has completed the passage.

## 🔒 Require a completed step

```php
Route::get('/advanced', AdvancedController::class)
    ->middleware([
        'auth',
        'passage.step:account-setup,verify-email',
    ]);
```

Use step gating when a feature depends on one milestone but not the entire passage.

## 🧭 Redirect to the next step

```php
Route::get('/onboarding', OnboardingController::class)
    ->middleware([
        'auth',
        'passage.next:account-setup',
    ]);
```

This middleware resolves the next configured step destination.

Ensure every user-facing actionable step has a valid route or URL destination.

## 🤖 Automatic enrollment

The default configuration includes:

```php
'middleware' => [
    'auto_enroll' => true,
    'incomplete_route' => null,
    'json_status' => 409,
],
```

When automatic enrollment is enabled, middleware can create an enrollment for a defined passage when one does not already exist.

Disable it when enrollment must be explicitly authorized or initiated by domain logic:

```php
'middleware' => [
    'auto_enroll' => false,
],
```

## 🚧 Incomplete route

Set `incomplete_route` to a named route used when middleware needs a general fallback:

```php
'middleware' => [
    'incomplete_route' => 'onboarding.show',
],
```

Avoid redirect loops. The fallback route itself should not be protected by middleware that redirects back to the same route.

## 🔌 JSON requests

JSON requests receive a configurable incomplete response. The default status is `409 Conflict`:

```php
'middleware' => [
    'json_status' => 409,
],
```

The response contains a progress snapshot suitable for clients that need to render the missing passage or next step.

Client applications should handle this status intentionally rather than treating every `409` as a generic server error.

## 🔐 Middleware is not authorization

Passage middleware answers workflow questions such as:

- has this user completed onboarding?
- has this prerequisite task been completed?
- where should this user continue?

It does not answer authorization questions such as:

- may this user edit this account?
- does this user own this resource?
- may this administrator force-complete a step?

Use policies and gates in addition to Passage middleware.

## 🗺️ Route organization

A clear route layout:

```php
Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding', OnboardingController::class)
        ->name('onboarding.show');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('passage.complete:account-setup');

    Route::get('/profile', EditProfileController::class)
        ->name('profile.edit');

    Route::get('/email/verify', VerificationNoticeController::class)
        ->name('verification.notice');
});
```

## 🐛 Debugging middleware

When a route redirects unexpectedly:

1. verify the subject is authenticated;
2. verify the passage definition is registered;
3. inspect `passageProgress()`;
4. confirm the enrollment exists;
5. check prerequisite states;
6. confirm the destination route exists;
7. inspect middleware configuration;
8. check the audit history.
