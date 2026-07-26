# 🧪 Testing

```bash
composer install
composer validate --strict
composer format
composer analyse
composer test
```

Run everything:

```bash
composer quality
```

The included tests use Orchestra Testbench and SQLite in memory. Application tests should cover custom conditions, authorization around administrative overrides, middleware redirects, notification routing, and scheduled commands.
