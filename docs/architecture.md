# 🏗️ Architecture

Passage is organized around definitions, a manager, persisted Eloquent models, middleware, commands, conditions, events, and audits.

## 🧩 Main components

### `PassageRegistry`

The registry stores passage definitions registered by fluent code or hydrated from configuration.

It provides a central definition lookup for the manager and middleware.

### 🗺️ `PassageDefinition`

Represents one passage blueprint, including ordered steps and passage-level options.

### 🪜 `StepDefinition`

Represents one task inside a passage, including requirements, prerequisites, destination, conditions, and metadata.

### `PassageManager`

Coordinates workflow operations:

- enrollment;
- progress resolution;
- transition validation;
- prerequisite checks;
- synchronization;
- completion calculation;
- restart behavior;
- event dispatch;
- audit recording.

Application code normally reaches the manager through the `Passage` facade or the `HasPassages` trait.

### `PassageEnrollment`

Persists one subject's passage cycle.

The subject relation is polymorphic, allowing any Eloquent model to participate.

### `PassageStepProgress`

Persists one step's state for one enrollment.

### `PassageAudit`

Persists lifecycle and administrative history for an enrollment.

### `StepCondition`

Defines an application-specific automatic completion rule.

### 🛡️ Middleware

Middleware connects passage state to HTTP access and continuation redirects.

### ⏱️ Commands

Commands provide batch operations for synchronization, expiration, reminders, repair, and pruning.

## ▶️ Data flow: enrollment

```text
Application
    |
    | startPassage("account-setup")
    v
HasPassages trait
    |
    v
PassageManager
    |
    +--> PassageRegistry -> PassageDefinition -> StepDefinitions
    |
    +--> PassageEnrollment
    |
    +--> PassageStepProgress rows
    |
    +--> lifecycle event
    |
    +--> PassageAudit
```

## ✅ Data flow: completing a step

```text
completePassageStep()
    |
    v
Resolve current enrollment and definition
    |
    v
Validate step and prerequisites
    |
    v
Persist transition
    |
    +--> update attempts/timestamps/metadata
    +--> record audit
    +--> dispatch step event
    |
    v
Recalculate passage completion
    |
    +--> complete enrollment when required work is satisfied
    +--> dispatch passage event
```

## 🔄 Data flow: synchronization

```text
Passage::sync() or passage:sync
    |
    v
Load active enrollment
    |
    v
Resolve visible automatic steps
    |
    v
Evaluate StepCondition
    |
    v
Apply eligible transitions
    |
    v
Recalculate progress and completion
```

## 🧱 Definition and persistence boundary

Definitions are code/configuration. Progress is database state.

This separation allows:

- one definition to serve many subjects;
- definition display text to evolve;
- persisted progress to survive deployments;
- repairs to reconcile definition changes.

It also means structural definition changes require an intentional migration or repair strategy.

## 🔒 Transactions and side effects

State transitions should be treated as atomic workflow operations.

Keep external side effects outside the critical transition when possible:

1. transition Passage state;
2. commit;
3. react through an event or queued job.

This avoids holding database work open while waiting for mail, HTTP APIs, or slow application services.

## 🔌 Extensibility boundaries

Preferred extension points:

- fluent or configuration definitions;
- condition classes;
- custom models;
- custom table names;
- event listeners;
- application services;
- notification configuration.

Avoid direct writes to internal model states because they can bypass manager invariants.
