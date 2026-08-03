# ✅ Testing

Passage behavior should be tested at three levels:

1. definition tests;
2. workflow transition tests;
3. HTTP or middleware integration tests.

## 🗄️ Test database setup

Run Passage migrations in the test environment.

For package development with Orchestra Testbench, load package migrations and use Laravel's database refresh support.

For an application test suite, `RefreshDatabase` is usually sufficient after the package migrations are installed:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

A `no such table: passage_enrollments` error means the package migrations are not being loaded or executed in the test database.

## 🧭 Register definitions in tests

Passage definitions must be registered before the operation under test.

A test helper can keep setup consistent:

```php
protected function defineAccountSetup(): void
{
    Passage::define('account-setup')
        ->name('Account setup')
        ->step('verify-email', function (StepDefinition $step): void {
            $step->name('Verify your email');
        })
        ->step('profile', function (StepDefinition $step): void {
            $step
                ->name('Complete profile')
                ->dependsOn('verify-email');
        });
}
```

## ▶️ Test enrollment creation

```php
public function test_a_user_can_start_a_passage(): void
{
    $this->defineAccountSetup();

    $user = User::factory()->create();

    $enrollment = $user->startPassage('account-setup');

    $this->assertSame('account-setup', $enrollment->passage_key);
    $this->assertCount(2, $enrollment->steps);
}
```

Adjust attribute names to the public model API in the installed release.

## 🔗 Test prerequisites

```php
public function test_a_step_is_blocked_until_its_prerequisite_is_satisfied(): void
{
    $this->defineAccountSetup();

    $user = User::factory()->create();
    $user->startPassage('account-setup');

    $this->expectException(StepBlocked::class);

    $user->completePassageStep('account-setup', 'profile');
}
```

Also test the successful path after completing the prerequisite.

## ✅ Test completion

```php
public function test_required_steps_complete_the_passage(): void
{
    $this->defineAccountSetup();

    $user = User::factory()->create();
    $user->startPassage('account-setup');

    $user->completePassageStep('account-setup', 'verify-email');
    $user->completePassageStep('account-setup', 'profile');

    $this->assertSame(
        100,
        $user->passageProgress('account-setup')->percentage,
    );
}
```

## ⏭️ Test optional steps

Verify that:

- optional steps can be skipped;
- required steps reject normal skip attempts;
- optional unfinished work does not prevent passage completion.

## 🤖 Test conditions

Cover:

- condition evaluates false;
- condition evaluates true;
- prerequisite blocks an otherwise true condition;
- repeated synchronization is safe;
- missing related data is handled.

## 📣 Test events

```php
Event::fake();

$user->completePassageStep(
    'account-setup',
    'verify-email',
);

Event::assertDispatched(StepCompleted::class);
```

## 🛡️ Test middleware

```php
public function test_incomplete_users_cannot_open_the_dashboard(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect();
}
```

For JSON:

```php
$this->actingAs($user)
    ->getJson('/dashboard')
    ->assertStatus(409)
    ->assertJsonStructure(['progress']);
```

Match the exact response shape exposed by the installed package.

## 🔄 Test definition changes

When changing a live definition, include a regression test that:

1. creates progress under the old shape;
2. registers the new shape;
3. runs repair;
4. verifies preserved and new progress;
5. verifies completion and next-step resolution.

## 🧪 Package quality commands

```bash
composer validate --strict
composer format
composer analyse
composer test
composer quality
```

A release should pass all quality commands on every supported PHP and Laravel combination.
