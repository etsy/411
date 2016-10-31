<?php

/**
 * 411 configuration file
 *
 * Rename this to 'config.php' and modify.
 */
$config = [];

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
     * 'hosts': An array of hosts powering your ES cluster.
     * 'index_hosts': An array of hosts to use for indexing (if different from 'hosts').
     * 'ssl_cert': Path to an ssl certificate if your cluster uses HTTPS.
     * 'index': The index to query. Leave as null to query all indices.
     * 'date_based': Whether to generate an index name according to a date-based index pattern.
     * 'date_field': The field to use for doing date-based queries.
     * 'date_type': The format of the date field.
     *              Leave as null to detect the date format. Should work most of the time.
     *              Specify a DateTime format to force its use.
     *              Specify '@' to force parsing the date as a unix timestamp.
     *              Specify '#' to force parsing the date as a unix timestamp (in milliseconds).
     * 'src_url': A format string for generating default source links.
     *            Requires the following format specifiers: 's', 'd', 'd'.
     *            Ex: 'https://localhost/?query=%s&from=%d&to=%d'
     */

    # Configuration for the 411 Alerts index.
    'alerts' => [
        'hosts' => ['http://localhost:9200'],
        'index_hosts' => [],
        'ssl_cert' => null,
        'index' => null,
        'date_based' => false,
        'date_field' => 'alert_date',
        'src_url' => null,
    ],
    # Configuration for the logstash index that 411 queries.
    'logstash' => [
        'hosts' => ['http://localhost:9200'],
        'index_hosts' => [],
        'ssl_cert' => null,
        'index' => 'logstash',
        'date_based' => true,
        'date_field' => '@timestamp',
        'src_url' => null,
    ],
];

/**
 * Graphite
 *
 * Configure to allow querying Graphite.
 */
$config['graphite'] = [
    /**
     * The hostname for your Graphite instance.
     */
    'host' => null,
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
    'webhook_url' => null
];
