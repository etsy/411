<?php

namespace FOO;

/**
 * Class Alert_Search
 * Executes a search against the 411 Alerts index. Aka: "so you can alert on your Alerts!"
 * @package FOO
 */
class Alert_Search extends Elasticsearch_Search {
    public static $TYPE = 'alert';
    public static $SOURCES = false;

    public function getConfig() {
        $cfg = Config::get(static::$CONFIG_KEY);
        $cfg = Util::get($cfg, 'alerts');
        $cfg['index'] = ESClient::getIndexName();
        return $cfg;
    }

    protected function getClientConfigKey() {
        return 'alerts';
    }

    protected function constructQuery() {
        list($settings, $query_list, $fields, $date_field, $result_type, $filter_range) = parent::constructQuery();

        if(!Util::exists($settings, 'fields')) {
            $settings['fields'] = $fields = [
                'alert_date',
                'assignee_type',
                'assignee',
                'content',
                'source',
                'source_id',
                'search_id',
                'state',
                'resolution',
                'escalated',
                'content_hash',
            ];
        }
        return [$settings, $query_list, $fields, $date_field, $result_type, $filter_range];
    }

    protected function _getLink(Alert $alert) {
        if($alert['source_id']) {
            return null;
        }

        $parts = explode('/', $alert['source_id'], 3);
        if(count($parts) != 3) {
            return null;
        }

        $this->generateLink($parts[2]);
    }

    public function generateLink($alert_id, $a, $b) {
        $site = SiteFinder::getCurrent();
        return $site->urlFor(sprintf('alert/%d', $alert_id));
    }
}
