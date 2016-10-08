<?php

namespace FOO;

/**
 * Class Graphite_Search
 * Queries graphite for data and generate alerts if the results exceed some threshold.
 * @package FOO
 */
class Graphite_Search extends Search {
    public static $TYPE = 'graphite';

    // Threshold type.
    /** Alert on values greater than. */
    const F_GREATER_THAN = 0;
    /** Alert on values less than. */
    const F_LESS_THAN    = 1;

    public function isWorking($date) {
        $gcfg = Config::get('graphite');
        if(is_null($gcfg['host'])) {
            return false;
        }
        $curl = new \Curl\Curl;
        $curl->head(sprintf('%s/render', $gcfg['host']));
        return $curl->httpStatusCode == 200;
    }

    public function isTimeBased() {
        return true;
    }

    protected function constructQuery() {
        return $this->obj['query_data'];
    }

    protected function _execute($date, $constructed_qdata) {
        $gcfg = Config::get('graphite');
        if(is_null($gcfg['host'])) {
            throw new SearchException('Graphite not configured');
        }
        $curl = new \Curl\Curl;
        $params = [
            'target' => sprintf('transformNull(%s)', $constructed_qdata['query']),
            'format' => 'raw',
            'from' => '-10mins',
            'until' => $date
        ];
        $raw_data = $curl->get(sprintf('%s/render?%s', $gcfg['host'], http_build_query($params)));

        if($curl->httpStatusCode != 200) {
            throw new SearchException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }

        $chunks = explode('|', $raw_data, 2);
        $data = explode(',', $chunks[1]);
        if(count($data) < 2) {
            throw new SearchException('Graphite returned less than two entries');
        }

        $value = $data[count($data) - 2];

        $ok = true;
        switch($constructed_qdata['filter_type']) {
            case self::F_GREATER_THAN: // Greater than
                $ok = $value <= $constructed_qdata['filter_threshold'];
                break;
            case self::F_LESS_THAN: // Less than
                $ok = $value >= $constructed_qdata['filter_threshold'];
                break;
        }

        if($ok) {
            return [];
        }

        $alert = new Alert;
        $alert['alert_date'] = $date;
        $alert['content'] = ['count' => $value];

        return [$alert];
    }

    protected function _getLink(Alert $alert) {
        return $this->generateLink(
            Util::get($this->obj['query_data'], 'query'),
            Util::get($this->obj['query_data'], 'filter_threshold', 0),
            $alert['alert_date'] - (10 * 60),
            $alert['alert_date']
        );
    }

    public function generateLink($query, $threshold, $start, $end) {
        $gcfg = Config::get('graphite');
        if(is_null($gcfg['host'])) {
            return null;
        }

        $params = [
            'target' => $query,
            'width' => 800,
            'height' => 600,
            'from' => $start,
            'until' => $end,
            'drawNullAsZero' => true
        ];

        return sprintf('%s/render?%s&target=alias(constantLine(%d),"Threshold")', $gcfg['host'], http_build_query($params), $threshold);
    }
}
