# 🚪 Usage

```php
$enrollment = Passage::enroll($user, 'account-setup');
$progress = Passage::progress($user, 'account-setup');
$next = Passage::nextStep($user, 'account-setup');
```

## State transitions

```php
Passage::startStep($user, 'account-setup', 'profile');
Passage::completeStep($user, 'account-setup', 'profile');
Passage::skipStep($user, 'account-setup', 'tour');
Passage::failStep($user, 'account-setup', 'profile', 'Validation failed');
Passage::cancel($user, 'account-setup');
Passage::restart($user, 'account-setup');
```

Required steps determine passage completion. Optional skipped or incomplete steps do not prevent completion. Prerequisite steps must be completed or skipped before dependent steps can proceed.

## Administrative actions

Pass an actor and `force: true` when a trusted administrator overrides normal prerequisite or skip behavior. The action is recorded in the audit log.
