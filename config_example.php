<?php

/**
 * 411 configuration file
 *
 * Rename this to 'config.php' and modify.
 */
$config = [];

/**
 * Authentication configuration
 */
$config['auth'] = [
    'proxy' => [
        /**
         * Whether to enable proxy auth.
         * IT IS VERY INSECURE TO ENABLE THIS IF 411 is not run behind an auth proxy.
         */
        'enabled' => false,
        /**
         * The name of the header the proxy is setting.
         */
        'header' => null,
        /**
         * Whether to automatically create users who are authenticated.
         */
        'auto_create' => false,
        /**
         * Email domain for automatically created users.
         */
        'domain' => null,
    ],
    'api' => [
        /**
         * Whether to enable api access to 411.
         */
        'enabled' => true
    ]
];

/**
 * Database configuration
 */
$config['db'] = [
    /**
     * A PDO compatible DSN. See https://secure.php.net/manual/en/pdo.drivers.php for details.
     * SQLite is the default configuration but MySQL is also supported. To configure the latter,
     * you'll need a dsn like the following: 'mysql:host=localhost;dbname=fouroneone'.
     */
    'dsn' => 'sqlite:' . realpath(__DIR__ . '/data.db'),
    /**
     * The user name for connecting to the database.
     */
    'user' => 'root',
    /**
     * The password for connecting to the database. Optional if the PDO driver doesn't
     * require a password.
     */
    'pass' => null,
];


/****
 *
 * Search type configuration
 * Note: All hostnames should specify protocol!
 *
 ***/

/**
 * Elasticsearch search type
 */
$config['elasticsearch'] = [
    /**
     * Each entry in this array represents an Elasticsearch source that 411 can query.
     *
     * 'hosts': An array of hosts powering your ES cluster. Credentials can be passed in the url.
     *          Ex: 'http://user:pass@localhost:9200'
     * 'index_hosts': An array of hosts to use for indexing (if different from 'hosts').
     * 'ssl_cert': Path to an ssl certificate if your cluster uses HTTPS.
     * 'ssl_client_cert': Path to an ssl client certificate if your cluster uses HTTPS.
     * 'ssl_client_key': Path to an ssl client key if your cluster uses HTTPS.
     * 'index': The index to query. Leave as null to query all indices.
     *          If the index is date_based, accepts index patterns. (Otherwise, it's taken literally)
     *          Any characters wrapped by [] will be taken literally.
     *          All other characters are interpretted via PHP's date formatting syntax.
     *          See https://secure.php.net/manual/en/function.date.php for details.
     * 'date_based': Whether to generate an index name according to a date-based index pattern.
     * 'date_interval': If the index is date_based, this defines the indexing pattern interval.
     *                  'h' - Hourly.
     *                  'd' - Daily.
     *                  'w' - Weekly.
     *                  'm' - Monthly.
     *                  'y' - Yearly.
     * 'date_field': The field to use for doing date-based queries.
     * 'date_type': The format of the date field.
     *              null - Automatically detect and parse. Should work most of the time!
     *              '@' - Parse as a UNIX timestamp.
     *              '#' - Parse as a UNIX timestamps (in milliseconds).
     *              All other strings are interpretted via PHP's date formatting syntax.
     * 'src_url': A server name for generating source links.
     *            Ex: 'https://localhost:5601'
     *
     * 'src_index_pattern_id': index pattern id to use in source links
     */

    # Configuration for the 411 Alerts index.
    'alerts' => [
        'hosts' => ['http://localhost:9200'],
        'index_hosts' => [],
        'ssl_cert' => null,
        'ssl_client_cert' => null,
        'ssl_client_key' => null,
        'index' => null,
        'date_based' => false,
        'date_interval' => null,
        'date_field' => 'alert_date',
        'date_type' => null,
        'src_url' => null,
    ],
    # Configuration for the logstash index that 411 queries.
    'logstash' => [
        'hosts' => ['http://localhost:9200'],
        'index_hosts' => [],
        'ssl_cert' => null,
        'ssl_client_cert' => null,
        'ssl_client_key' => null,
        'index' => '[logstash-]Y.m.d',
        'date_based' => true,
        'date_interval' => 'd',
        'date_field' => '@timestamp',
        'date_type' => null,
        'src_url' => null,
        'src_index_pattern_id' => null
    ],
];

/**
 * Graphite
 *
 * Configure to allow querying Graphite.
 */
$config['graphite'] = [
    /**
     * Each entry in this array represents a Graphite source that 411 can query.
     *
     * 'host': The hostname for your Graphite instance.
     */
    'graphite' => [
        'host' => null,
    ],
];

/**
 * ThreatExchange
 * See https://developers.facebook.com/products/threat-exchange for details.
 *
 * Configure to allow querying ThreatExchange.
 */
$config['threatexchange'] = [
    /**
     * The api token for connecting to ThreatExchange.
     */
    'api_token' => null,
    /**
     * The api secret for connecting to ThreatExchange.
     */
    'api_secret' => null,
];


/****
 *
 * Target configuration
 *
 ***/

/**
 * Jira
 *
 * Fill in to enable Jira integration.
 */
$config['jira'] = [
    /**
     * The hostname for your Jira instance.
     */
    'host' => null,
    /**
     * The username for connecting to Jira.
     */
    'user' => null,
    /**
     * The password for connecting to Jira.
     */
    'pass' => null,
];

/**
 * Slack
 *
 * Fill in to enable Slack integration.
 */
$config['slack'] = [
    /**
     * A webhook url to push Alerts to.
     * See https://api.slack.com/incoming-webhooks for details.
     */
    'webhook_url' => null,
    /**
     * The username to display on Alerts.
     */
    'username' => null,
    /**
     * The icon to display on Alerts.
     */
    'icon' => null,
];
