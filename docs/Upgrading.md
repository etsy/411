Upgrading
=========

Whenever you upgrade to a new version of 411, make sure to do the following before moving to any version specific instructions.

Update dependencies
```
$ composer install --no-dev --optimize-autoloader
```

Run the migration script:
```
$ bin/migration.php
Migrating from A.B.C to D.E.F
...
Migration complete!
```

## v1.2 ##

With the introduction of Search sources, changes were made to the following Searches:

- `Logstash`: Removed.
- `ES`: Supercedes `Logstash` and supports sources.
- `Graphite`: Modified to support sources.

If you're using any `Logstash` or `Graphite` Searches, you'll need to make some changes:

### Graphite ###

The config should now contain an associative array of sources. Ex:
```
$config['graphite'] = [
    'dev' => [
        'host' => 'devgraphite.example.com',
    ],
    'prod' => [
        'host' => 'prodgraphite.example.com',
    ],
];
```
Where `dev` and `prod` are the name of the sources.

If you previously had any `Graphite` Searches set up, you'll need to run the following query (substitute `TYPE` with the source name) to update the db.
```
UPDATE `searches` SET `source`="TYPE", `type`="graphite" WHERE `type`="TYPE"
```

If you've created any Search types that subclass `Graphite_Search`, you'll need run some SQL queries to update the database.

### Logstash ###

Any `Logstash` Searches should be automatically migrated by the migration script. However, if you've created any Search types that subclass `Elasticsearch_Search`, you'll need to run the following query (substitute `TYPE` with the source name):
```
UPDATE `searches` SET `source`="TYPE", `type`="es" WHERE `type`="TYPE"
```

### Reindexing ###

After you've made any changes necessary, you'll have to reindex alerts into ES (substitute `SITE` with the site id, which is usually 1):
```
$ bin/es_sync --site=SITE
```

If you have multiple 411 instances set up, you'll need to rerun `es_sync` for each site id.
