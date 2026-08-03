# 🔐 Security

Passage tracks journey state. It is not an authorization system.

## 🛡️ Continue using Laravel authorization

Protect sensitive operations with:

- policies;
- gates;
- ownership checks;
- role or permission checks;
- authenticated middleware;
- signed URLs where appropriate.

A completed passage does not automatically grant permission to a resource.

## 🔐 Authorize administrative overrides

Forced transitions can bypass normal workflow constraints:

```php
Passage::completeStep(
    subject: $user,
    passage: 'seller-verification',
    step: 'manual-review',
    actor: $administrator,
    force: true,
);
```

Before calling them:

1. authorize the actor;
2. validate the target subject;
3. capture a concise reason;
4. preserve actor attribution;
5. alert or review high-risk actions when required.

Never expose `force` directly from untrusted request input without an authorization boundary.

## ⚠️ Treat metadata as untrusted data

Enrollment, step, and audit metadata may contain application-supplied values.

Validate and normalize request data before storing it. Escape values when rendering them.

Do not store:

- passwords;
- tokens;
- private keys;
- session identifiers;
- full payment details;
- unnecessary identity documents;
- raw request bodies.

## 🤖 Condition safety

Condition evaluation can run during requests or scheduled synchronization.

Conditions should not:

- execute arbitrary user-provided classes;
- evaluate untrusted code;
- issue destructive side effects;
- trust user-editable fields without domain validation;
- call unbounded external endpoints.

Only register trusted condition class strings.

## 🧭 Redirect safety

Named routes are safer than arbitrary external URLs.

When using direct URLs:

- restrict allowed schemes;
- avoid user-controlled destinations;
- prevent open redirects;
- ensure HTTPS where required.

## 🔔 Reminder privacy

Reminder channels can expose passage names, deadlines, or account status.

Confirm:

- the correct recipient;
- notification preferences;
- queue and mail security;
- sensitive copy rules;
- rate limiting and cooldowns.

## 🧾 Audit access

Audit history can reveal:

- account activity;
- administrative actions;
- failure reasons;
- internal workflow structure;
- actor identity.

Protect audit endpoints with strict policies and log sensitive access where appropriate.

## 🗄️ Database integrity

Avoid direct state updates:

```php
$step->update(['state' => 'completed']); // Avoid.
```

Direct writes can bypass prerequisites, events, completion recalculation, and audits.

Use Passage's public transition API.

## 🏁 Race conditions

Concurrent requests may try to complete or fail the same step.

Design application integrations to be idempotent. Use database transactions or application-level locking around additional domain side effects when duplicate execution matters.

## 🧹 Retention and deletion

Pruning permanently removes eligible history.

Before enabling it:

- document retention periods;
- consider legal holds;
- confirm support requirements;
- test backups and restoration;
- review audit requirements.

## 🚨 Vulnerability reporting

Report package vulnerabilities privately using the repository's `SECURITY.md` process rather than opening a public issue with exploit details.
