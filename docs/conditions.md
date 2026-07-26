# 🤖 Conditions and Automation

A completion or visibility condition may be a closure or a class implementing `StepCondition`.

```php
final class ProfileComplete implements StepCondition
{
    public function evaluate(Model $subject, PassageEnrollment $enrollment, PassageStepProgress $step): bool
    {
        return $subject->profile?->isComplete() === true;
    }
}
```

Run synchronization after relevant application changes:

```php
Passage::sync($user, 'account-setup');
```

Or schedule the command:

```php
Schedule::command('passage:sync')->hourly();
```

Invisible optional steps are automatically skipped during synchronization. Invisible required steps remain incomplete so an accidentally hidden requirement cannot silently complete a passage.
