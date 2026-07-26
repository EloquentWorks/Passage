# Upgrading Laravel Passage

## Unreleased

No upgrade instructions are currently required.

When upgrading future versions:

1. Review `CHANGELOG.md`.
2. Publish newly introduced migrations without overwriting customized files.
3. Run `php artisan migrate`.
4. Review definition-version changes.
5. Run `php artisan passage:repair --dry-run` when that option is introduced or inspect active enrollments before repair.
6. Run the application test suite.
