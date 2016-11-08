Upgrading
=========

Update dependencies
```
$ composer install --no-dev --optimize-autoloader
```

Run the db migration script:
```
$ bin/migration.php
Migrating from A.B.C to D.E.F
...
Migration complete!
```
