Setup
=====

Dependencies
------------

- Apache 2
- PHP 5.5 with SQLite3 support
- Sqlite 3.3+/MySQL
- Git
- Composer
- Elasticsearch 2.0+

Ubuntu Packages:
```
$ sudo apt-get install git apache2 libapache2-mod-php php-xml php7.0-mbstring php7.0-sqlite php7.0-curl sqlite3
```

Fedora Packages:
```
$ sudo dnf install git httpd php php-posix php-pdo php-xml sqlite
```

Composer:
```
$ curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

MTA:

Here's a guide on setting up [Exim](https://www.digitalocean.com/community/tutorials/how-to-install-the-send-only-mail-server-exim-on-ubuntu-12-04).

Elasticsearch:

This guide assumes you already have an Elasticsearch cluster ingesting data from Logstash. You can use that cluster for storing 411 alerts or set up a separate cluster entirely. For the latter, here's a guide on setting up [Elasticsearch](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-elasticsearch-on-ubuntu-16-04)


Set up Apache:

Enable the `mod_rewrite` and `mod_headers` Apache modules.

Install
-------

Grab the newest [release](https://github.com/etsy/411/releases).

Set up Apache VHost:

[411.conf](/411.conf) is a sample vhost config you can use. Copy it into the appropriate directory and replace `HOSTNAME` with the hostname of the box.

Enable the `mod_rewrite` and `mod_headers` Apache modules.
```
a2enmod rewrite
a2enmod headers
```

Move the 411 directory into `/var/www`. You may have to fix permissions on the directory so that the database is writable by Apache.

Identify the corresponding `composer-*.json' file for your ES cluster. The command below assumes you're running ES6.0.

Install dependencies:
```
$ COMPOSER=composer-es6x.json composer install --no-dev --optimize-autoloader
```

Create the database (sqlite):
```
$ sqlite3 data.db < db.sql
```

Create the database (mysql):
```
$ mysql
> create database fouroneone
> use fouroneone
> source ./db_mysql.sql
```

Rename `config_example.php` to `config.php` and make changes as necessary.

Run the db migration script:
```
$ bin/migration.php
Migrating from A.B.C to D.E.F
Migration complete!
```

Create a new 411 site:
```
# Run the bin/create_site.php script and answer the questions.
# Important: The hostname has to match what you've configured in the vhost. (Including the port, if it's nonstandard)
# If this is not possible, set the `FOURONEONEHOST` value in the vhost to the same value.
$ bin/create_site.php
Creating new site
Site name: FourOneOne
Hostname: demo.fouroneone.io
From email: alert@fouroneone.io
Default To email: admin@fouroneone.io

Site created! ID: 1
```

Note that 411 supports multiple sites under the same database! To create another site, just re-run `create_site.php`.

Create a new user:
```
# Run the bin/create_user.php script and answer the questions.
$ bin/create_user.php
Creating new user
Username: admin
Real name: Admin
Password: 1
Email: admin@fouroneone.io
Admin (y/n): y

User created! ID: 1
```

Set up the cron:
```
# Add the following line into your crontab.
$ crontab -u USER -e
* * * * * /var/www/411/bin/cron.php > /dev/null 2>&1 && /var/www/411/bin/worker.php > /dev/null 2>&1
```

That's all! Next, check out the [guide](/docs/GettingStarted.md) to get a Search set up.
