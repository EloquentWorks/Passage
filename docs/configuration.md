# ⚙️ Configuration

`config/passage.php` controls:

- table names;
- replaceable model classes;
- middleware enrollment and fallback behavior;
- reminder look-ahead, cooldown, and channels;
- pruning retention periods;
- configuration-defined passages.

When replacing models, extend the package model and preserve its relationships, fillable fields, casts, and expected API.
