# 🧭 Defining Passages

Passages may be registered fluently or in configuration. Fluent definitions support closures and class-string conditions. Config definitions should use condition classes so configuration caching remains safe.

```php
Passage::define('seller-onboarding')
    ->name('Seller onboarding')
    ->version(3)
    ->repeatable(false)
    ->dueAfterMinutes(10080)
    ->step('identity', fn (StepDefinition $step) => $step->route('seller.identity'))
    ->step('banking', fn (StepDefinition $step) => $step->dependsOn('identity'))
    ->step('tour', fn (StepDefinition $step) => $step->optional());
```

A version is copied onto each enrollment. Increasing the definition version does not rewrite historical enrollments. Use `passage:repair` to add newly defined step rows to active enrollments when that behavior is desired.

## Step options

- `name()` and `description()`
- `required()` or `optional()`
- `dependsOn()`
- `route()` or `url()`
- `dueAfterMinutes()`
- `retryable()` and maximum attempts
- `completeWhen()`
- `visibleWhen()`
- `metadata()`
