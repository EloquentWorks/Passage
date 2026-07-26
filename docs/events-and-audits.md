# 📣 Events and Audit History

Passage dispatches lifecycle events after persistent state changes. Events contain the enrollment and, where relevant, the step, actor, override status, or failure reason.

Audit records include:

- passage and step keys;
- subject morph identity;
- optional actor morph identity;
- event name;
- structured data;
- occurrence time.

Use audit records for internal accountability and troubleshooting. They are not a cryptographically immutable ledger.
