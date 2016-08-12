<?php

namespace FOO;

/**
 * Class Alert_Search
 * Executes a search against the 411 Alerts index. Aka: "so you can alert on your Alerts!"
 * @package FOO
 */
class Alert_Search extends Elasticsearch_Search {
    public static $TYPE = 'alert';
    public static $CONFIG_NAME = 'alerts';

    public static function getConfig() {
        static $config = null;
        if(is_null($config)) {
            $config = parent::getConfig();
            $config['index'] = ESClient::getIndexName();
        }

        return $config;
    }

    protected function constructQuery() {
        list($settings, $query_list, $fields, $date_field, $result_type, $filter_range) = parent::constructQuery();

        if(!Util::exists($settings, 'fields')) {
            $settings['fields'] = $fields = [
                'alert_date',
                'assignee_type',
                'assignee',
                'content',
                'search_id',
                'state',
                'resolution',
                'escalated',
                'content_hash',
            ];
        }
        return [$settings, $query_list, $fields, $date_field, $result_type, $filter_range];
    }
}
