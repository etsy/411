Setup
=====

Dependencies
------------

- Apache 2
- PHP 5.5 with SQLite3 support
- Sqlite 3.3+
- Composer
- NPM
- Bower
- Grunt
- Elasticsearch 1.5+

Ubuntu Packages:
```
$ sudo apt-get install apache2 libapache2-mod-php php-xml php7.0-mbstring php7.0-sqlite php7.0-curl nodejs-legacy npm sqlite3
```

Fedora Packages:
```
$ sudo dnf install httpd php php-posix php-pdo php-xml sqlite nodejs
```

Composer:
```
$ curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Grunt & Bower:
```
$ sudo npm install -g grunt-cli bower
```

MTA:

Here's a guide on setting up [Exim](https://www.digitalocean.com/community/tutorials/how-to-install-the-send-only-mail-server-exim-on-ubuntu-12-04).

Elasticsearch:

This guide assumes you already have an Elasticsearch cluster ingesting data from Logstash. You can use that cluster for storing 411 alerts or set up a separate cluster entirely. For the latter, here's a guide on setting up [Elasticsearch](https://www.digitalocean.com/community/tutorials/how-to-install-elasticsearch-on-an-ubuntu-vps)


Set up Apache:

Enable the `mod_rewrite` and `mod_headers` Apache modules.

Install
-------

Grab the repo:
```
$ git clone https://github.com/kiwiz/411.git
```

Set up Apache VHost:

[411.conf](/411.conf) is a sample vhost config you can use. Copy it into the appropriate directory and replace `HOSTNAME` with the hostname of the box.

Enable the `mod_rewrite` and `mod_headers` Apache modules.
```
a2enmod rewrite
a2enmod headers
```

Move the 411 directory into `/var/www`. You may have to fix permissions on the directory so that the database is writable by Apache.

Install dependencies:
```
$ npm install
$ bower install
$ composer install
```

Compile assets:
```
$ grunt prod
```

Modify config.php:
```
# If Elasticsearch is set up locally, you might not have to make any changes.
$config['elasticsearch'] = [
    # Used by 411 to store Alerts
    'alerts' => [
        'hosts' => ['localhost:9200'], # Host to retrieve Alerts from.
        'index_hosts' => [], # Host to push Alerts to. (If different)
        'ssl_cert' => null, # Path to SSL certificate if self-signed.
    ],

    # Data source for the Logstash Search
    'logstash' => [
        'hosts' => ['localhost:9200'], # Hosts to query for results.
        'index_hosts' => [], # Hosts to push lookup tables to. (If different)
        'ssl_cert' => null, # Path to SSL certificate if self-signed.
        'index' => 'logstash', # Index name (Without the date).
        'date_based' => true, # Whether to append a date to the index (`logstash` becomes `logstash-2016.01.01`).
        'date_field' => '@timestamp', # Field to use for date based queries.
        'src_url' => null, # Link to Kibana instance.
    ],
];
```

Create the database:
```
$ sqlite3 data.db < db.sql
```

Create a new 411 site:
```
# Run the bin/create_site.php script and answer the questions.
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
* * * * * /var/www/411/bin/cron.php && /var/www/411/bin/worker.php
```

That's all! Next, check out the [guide](/docs/GettingStarted.md) to get a Search set up.
