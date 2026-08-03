# 🧭 Core Concepts

Passage separates a workflow's **definition** from a subject's **persisted progress**.

## 🗺️ Passage definition

A passage definition is the blueprint for a journey.

A definition can describe:

- a stable key;
- a display name and description;
- a category;
- a version;
- tags;
- a passage deadline;
- whether it can repeat;
- ordered step definitions;
- definition metadata.

Definitions are registered in code or loaded from `config/passage.php`.

A definition does not represent one user's progress. It only describes what the journey is.

## 🪜 Step definition

A step definition describes one task inside a passage.

A step may be:

- required or optional;
- dependent on earlier steps;
- linked to a named route or URL;
- automatically completed by a condition;
- conditionally visible;
- associated with a deadline;
- governed by retry and maximum-attempt rules;
- decorated with metadata.

Step order matters because Passage uses the definition order when resolving the next actionable step.

## 👤 Enrollment

An enrollment is one subject's instance of a passage.

Examples of subjects include:

- users;
- teams;
- organizations;
- applications;
- orders;
- bots or agents;
- any other Eloquent model using `HasPassages`.

An enrollment stores passage-level state, dates, metadata, cycle information, and links to its persisted step progress.

Supported enrollment states include:

- pending;
- in progress;
- blocked;
- completed;
- expired;
- cancelled.

## 📈 Step progress

Passage persists a progress row for every synchronized step in an enrollment.

Supported step states include:

- pending;
- in progress;
- completed;
- skipped;
- failed;
- blocked.

The stored row can also track attempts, deadlines, completion information, failure information, and metadata.

## 🔗 Prerequisites

A prerequisite creates a dependency between steps.

```php
$step
    ->name('Complete your profile')
    ->dependsOn('verify-email');
```

Passage blocks the dependent step until its prerequisites are satisfied.

Use prerequisites for true workflow dependencies, not merely presentation order. A step can appear later in the definition without depending on every earlier step.

## ✅ Required and optional steps

Required steps contribute to passage completion.

Optional steps can be offered without preventing the passage from completing. They can also be skipped through the public API.

```php
$step
    ->name('Take the product tour')
    ->optional()
    ->route('tour.start');
```

A required step cannot normally be skipped unless an administrative override is used.

## 📊 Progress snapshot

`passageProgress()` returns a JSON-friendly snapshot of the current enrollment.

Commonly used properties include:

```php
$progress = $user->passageProgress('account-setup');

$progress->percentage; // 0 through 100
$progress->nextStep;   // next actionable step, when available
$progress->state;      // enrollment state
```

Passage resolves the next step from the current definition and persisted states, accounting for prerequisites and visibility.

## 🔢 Definition versions

A definition version identifies the shape of a passage.

Increment it when the meaning or structure of an existing journey changes. Pair version changes with `passage:repair` or an application-specific migration strategy so existing enrollments are intentionally synchronized.

Do not use definition versions as a replacement for database migrations or release versions.

## 🔁 Repeatable passages and cycles

A repeatable passage can be restarted after completion, cancellation, or another terminal state.

Each restart creates a new cycle rather than erasing the historical enrollment:

```php
$user->restartPassage('annual-review');
```

Cycle tracking allows applications to retain previous attempts while operating on the latest enrollment.

## 🧾 Audit history

Passage records important workflow activity in an audit history.

An audit can associate:

- an enrollment;
- an event name;
- a step key;
- an optional actor;
- structured data.

Audit history is useful for support, compliance, debugging, and administrative review, but it should not be treated as an immutable security ledger unless your application adds the required protections.
