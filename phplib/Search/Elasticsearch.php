<?php

namespace FOO;

/**
 * Class Elasticsearch_Search
 * Executes a query against an Elasticsearch cluster.
 * @package FOO
 */
abstract class Elasticsearch_Search extends Search {
    public static $TYPE = 'elasticsearch';
    public static $CONFIG_KEY = 'elasticsearch';
    public static $SOURCES = true;

    // Result types.
    /** Field data result type. */
    const R_FIELDS = 0;
    /** Count result type. */
    const R_COUNT = 1;
    /** No results result type. */
    const R_NO_RESULTS = 2;

    protected function _getLink(Alert $alert) {
        if($alert['source_id']) {
            $parts = explode('/', $alert['source_id'], 3);
            if(count($parts) == 3) {
                return $this->generateAlertLink($parts[0], $parts[1], $parts[2]);
            }
        }

        return $this->generateLink(
            Util::get($this->obj['query_data'], 'query'),
            $alert['alert_date'] - ($this->obj['range'] * 60),
            $alert['alert_date']
        );
    }

    public function generateAlertLink($index, $type, $id) {
        $cfg = $this->getConfig();

        if($cfg['src_index_pattern_id']) {
            $index_id = $cfg['src_index_pattern_id'];

        } else {
            $index_id = $cfg['date_based'] ? \ESQuery\Util::generateKibanaPattern($cfg['index']) : $cfg['index'];
        }

        return sprintf('%s/app/kibana#/doc/%s/%s/%s?%s', $cfg['src_url'], $index_id, $index, $type, http_build_query(['id' => $id]));
    }

    public function generateLink($query, $start, $end) {
        $cfg = $this->getConfig();
        if(is_null($cfg['src_url'])) {
            return null;
        }

        if($cfg['src_index_pattern_id']) {
            $index_id = $cfg['src_index_pattern_id'];

        } else {
            $index_id = $cfg['date_based'] ? \ESQuery\Util::generateKibanaPattern($cfg['index']) : $cfg['index'];
        }

        $parser = new \ESQuery\Parser;
        try {
            return $parser->generateUrl($query, $start, $end, $cfg['src_url'], $index_id);
        } catch(\ESQuery\Exception $e) {
            return null;
        }
    }

    public function isTimeBased() {
        return !is_null(Util::get($this->getConfig(), 'date_field'));
    }

    protected function getClientConfigKey() {
        return $this->obj['source'];
    }

    public function isWorking($date) {
        $cfg = $this->getConfig();
        $client = ESClient::getClient($this->getClientConfigKey());

        $working = false;
        try {
            if(is_null($cfg['index']) || !$this->isTimeBased()) {
                $client->cat()->health();
                $working = true;
            } else {
                $index = $cfg['index'];
                if(Util::get($cfg, 'date_based', false)) {
                    $index = \ECL\Util::generateDateIndices($cfg['index'], Util::get($cfg, 'date_interval', 'd'), $date, $date)[0];
                }
                $working = $client->indices()->exists(['index' => $index]);
            }
        } catch(\Exception $e) {}
        return $working;
    }

    protected function constructQuery() {
        $query = Util::get($this->obj['query_data'], 'query');
        $fields = Util::get($this->obj['query_data'], 'fields', []);
        $parser = new \ESQuery\Parser;
        list($settings, $query_list) = $parser->parse($query);

        $cfg = $this->getConfig();

        if(count($cfg['hosts']) > 0) {
            $settings['host'] = $cfg['hosts'][array_rand($cfg['hosts'])];
        }
        if(!is_null($cfg['index'])) {
            $settings['index'] = $cfg['index'];
        }
        $settings['date_based'] = $cfg['date_based'];
        if(!is_null($cfg['date_field'])) {
            $event_time_based = Util::get($this->obj['query_data'], 'event_time_based', false);

            $settings['date_field'] = $cfg['date_field'];
            // If a timestamp field was specified, make sure to always request it.
            if(count($fields)) {
                $fields[] = $cfg['date_field'];
            }
        }
        if(count($fields)) {
            $settings['fields'] = $fields;
        }
        return [
            $settings, $query_list, $fields, Util::get($cfg, 'date_field'), Util::get($cfg, 'date_type'),
            Util::get($this->obj['query_data'], 'result_type', 0),
            Util::get($this->obj['query_data'], 'filter_range', 0),
        ];
    }

    protected function _execute($date, $constructed_qdata) {
        list($settings, $query_list, $fields, $date_field, $date_type, $result_type, $filter_range) = $constructed_qdata;

        // If our last_success_date is within 10 seconds of the start time, use that
        // as the start time.
        $from = $date - ($this->obj['range'] * 60);
        if(abs($this->obj['last_success_date'] - $from) < 10) {
            $from = $this->obj['last_success_date'];
        }
        $settings['from'] = $from;
        $settings['to'] = $date;
        // Somewhat arbitrary size. 500 per day.
        if(!array_key_exists('size', $settings)) {
            $settings['size'] = (floor($this->obj['range'] / 1440) + 1) * 500;
        }

        return $this->search($date,
            $settings, $query_list,
            $fields, $date_field, $date_type,
            $result_type, $filter_range
        );
    }

    public function getList($name) {
        return Util::get($this->getListData([$name]), $name, []);
    }

    public function getConnection($host) {
        $cfg = $this->getConfig();

        $cb = \Elasticsearch\ClientBuilder::create();
        if(!is_null($cfg['ssl_cert'])) {
            $cb->setSSLVerification($cfg['ssl_cert']);
        }
        if(!is_null($cfg['ssl_client_key'])) {
            $cb->setSSLKey($cfg['ssl_client_key']);
        }
        if(!is_null($cfg['ssl_client_cert'])) {
            $cb->setSSLCert($cfg['ssl_client_cert']);
        }
        if(!is_null($host)) {
            $cb->setHosts([$host]);
        }

        return $cb->build();
    }

    /**
     * Execute an Elasticsearch query and return the results.
     * @param int $date What time this query was started.
     * @param array $settings The query settings.
     * @param array $query_list The query list.
     * @param array $fields A list of fields to include.
     * @param string $date_field The date field to pull the date from.
     * @param string $date_type The format of the date field.
     * @param int $result_type The type of result to return.
     * @param array[] $filter_range The lower and upper bounds for results. Use null to represent an unbounded side.
     * @return Alert[] A list of Alert results.
     * @throws SearchException
     */
    public function search($date, $settings, $query_list, $fields, $date_field, $date_type, $result_type, $filter_range) {
        // If we're looking for no results, set filter to (< 1)
        if($result_type == self::R_NO_RESULTS) {
            $filter_range = [0, 0];
        }

        // If we got a malformed filter range, overwrite.
        if(count($filter_range) != 2) {
            $filter_range = [null, null];
        }

        $alerts = [];
        try {
            // Agg results.
            $query_len = count($query_list);
            if($query_len > 0 && $query_list[$query_len - 1][0] == \ESQuery\Token::C_AGG) {
                $es = new \ESQuery\Scheduler($settings, $query_list, [$this, 'getConnection'], [$this, 'getList']);
                $data = $es->execute();

                switch($result_type) {
                    // Rows
                    case self::R_FIELDS:
                        foreach($data as $row) {
                            $ok = true;
                            $count = $row['count'];
                            if(!is_null($filter_range[0]) && $ok) {
                                $ok = $count >= $filter_range[0];
                            }
                            if(!is_null($filter_range[1]) && $ok) {
                                $ok = $count <= $filter_range[1];
                            }

                            if($ok) {
                                $alert = new Alert;
                                $alert['alert_date'] = $date;
                                $alert['content'] = $row;
                                $alerts[] = $alert;
                            }
                        }
                        break;

                    // Count
                    case self::R_COUNT:
                    // No data
                    case self::R_NO_RESULTS:
                        $ok = true;
                        $count = count($data);
                        if(!is_null($filter_range[0]) && $ok) {
                            $ok = $count >= $filter_range[0];
                        }
                        if(!is_null($filter_range[1]) && $ok) {
                            $ok = $count <= $filter_range[1];
                        }

                        if($ok) {
                            $alert = new Alert;
                            $alert['alert_date'] = $date;
                            $alert['content'] = ['count' => $count];
                            $alerts[] = $alert;
                        }
                        break;
                }

            // Normal results.
            } else {
                // Grab a count of results.
                $count_settings = $settings;
                $count_settings['count'] = true;
                $es = new \ESQuery\Scheduler($count_settings, $query_list, [$this, 'getConnection'], [$this, 'getList']);
                $count = $es->execute()['count'];

                // Determine whether continue processing.
                $ok = true;
                if(!is_null($filter_range[0]) && $ok) {
                    $ok = $count >= $filter_range[0];
                }
                if(!is_null($filter_range[1]) && $ok) {
                    $ok = $count <= $filter_range[1];
                }

                $underscore_fields = ['_index', '_type', '_id', '_score'];
                $field_specified = [];
                foreach($underscore_fields as $field) {
                    if(in_array($field, $fields)) {
                        $field_specified[$field] = null;
                    }
                }

                if($ok) {
                    switch($result_type) {
                        // Rows
                        case self::R_FIELDS:
                            $es = new \ESQuery\Scheduler($settings, $query_list, [$this, 'getConnection'], [$this, 'getList']);
                            $data = $es->execute();

                            foreach($data as $row) {
                                $alert = new Alert;
                                if (!array_key_exists('time', $row)) {
                                    $alert_date = $date;
                                    if (array_key_exists($date_field, $row)) {
                                        // Extract the date field.
                                        $alert_date = Util::parseDates($date_type, [$row[$date_field]])[0] / 1000;
                                        unset($row[$date_field]);
                                    }
                                    $alert['alert_date'] = $alert_date;
                                }
                                $alert['source_id'] = sprintf('%s/%s/%s', $row['_index'], $row['_type'], $row['_id']);
                                foreach($underscore_fields as $field) {
                                    if(!array_key_exists($field, $field_specified)) {
                                        unset($row[$field]);
                                    }
                                }
                                $alert['content'] = $row;
                                $alerts[] = $alert;
                            }
                            break;

                        // Count
                        case self::R_COUNT:
                        // No data
                        case self::R_NO_RESULTS:
                            $alert = new Alert;
                            $alert['alert_date'] = $date;
                            $alert['content'] = ['count' => $count];
                            $alerts[] = $alert;
                            break;
                    }
                }
            }
        } catch(\Exception $e) {
            throw new SearchException($e->getMessage());
        }

        return $alerts;
    }
}
