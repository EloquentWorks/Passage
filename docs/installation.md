# 🚀 Installation

```bash
composer require eloquent-works/passage
php artisan passage:install --migrate
```

The install command publishes `config/passage.php` and the three package migrations. Add `HasPassages` to each participating Eloquent model.

```php
use EloquentWorks\Passage\Traits\HasPassages;

class User extends Authenticatable
{
    use HasPassages;
}
```

The subject key is persisted as a string, allowing integer, UUID, ULID, and other string-compatible model keys.
