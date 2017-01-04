<?php

namespace FOO;

/**
 * Class Logstash_Search
 * Queries a Logstash index on an Elasticsearch cluster.
 * @package FOO
 */
class Logstash_Search extends Elasticsearch_Search {
    public static $TYPE = 'logstash';
    public static $SOURCES = false;

    public function getConfig() {
        $cfg = Config::get(static::$CONFIG_KEY);
        return Util::get($cfg, 'logstash');
    }

    protected function getClientConfigKey() {
        return 'logstash';
    }
}
