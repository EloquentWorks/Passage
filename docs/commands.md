# ⏱️ Commands and Scheduling

```bash
php artisan passage:install --migrate
php artisan passage:sync
php artisan passage:expire
php artisan passage:expire --dry-run
php artisan passage:remind
php artisan passage:remind --dry-run
php artisan passage:repair
php artisan passage:repair account-setup
php artisan passage:prune --force
```

Recommended scheduling:

```php
Schedule::command('passage:sync')->hourly()->withoutOverlapping();
Schedule::command('passage:expire')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('passage:remind')->hourly()->withoutOverlapping();
Schedule::command('passage:prune')->daily()->withoutOverlapping();
```

Pruning is disabled by default. Review retention requirements before enabling it.
