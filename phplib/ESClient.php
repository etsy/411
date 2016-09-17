<?php

namespace FOO;

/**
 * Class ESClient
 * Contains functionality for managing the Alert index.
 * @package FOO
 */
class ESClient {
    /** Number of Alerts to batch into a single request. */
    const BATCH_SIZE = 1000;

    /** Name of the ES mapping template. */
    const MAPPING_TEMPLATE = '411_alerts_wildcard';

    /** @var Search[] Mapping of ids to Search objects. **/
    private $searches = [];
    /** @var Alert[] List of pending Alerts. **/
    private $list = [];
    /** @var string Index name. **/
    private $index;

    /**
     * @param boolean $init Whether to initialize the ES index (if it doesn't exist).
     */
    public function __construct($init=true) {
        $this->index = self::getIndexName();

        if($init) {
            $this->initializeIndex();
        }
    }

    /**
     * Returns the name of the Alerts index.
     * @return string Index name.
     */
    public static function getIndexName() {
        return '411_alerts_' . SiteFinder::getCurrentId();
    }

    /**
     * Get an ES client.
     * @param string $config_name The name of the es config key.
     * @param bool $index Whether this client will be used for indexing.
     * @return \Elasticsearch\Client The client object.
     */
    public static function getClient($config_name='alerts', $index=false) {
        $escfg = Config::get('elasticsearch')[$config_name];
        $cb = \Elasticsearch\ClientBuilder::create();
        if($index && count($escfg['index_hosts']) > 0) {
            $cb->setHosts($escfg['index_hosts']);
        } else if(count($escfg['hosts']) > 0) {
            $cb->setHosts($escfg['hosts']);
        }
        if(!is_null($escfg['ssl_cert'])) {
            $cb->setSSLVerification($escfg['ssl_cert']);
        }

        return $cb->build();
    }

    /**
     * Initialize the index as necessary.
     */
    public function initializeIndex() {
        $client = self::getClient('alerts', true);

        // Create template.
        if(!$client->indices()->existsTemplate(['name' => self::MAPPING_TEMPLATE])) {
            $client->indices()->putTemplate([
                'name' => self::MAPPING_TEMPLATE,
                'body' => [
                    'template' => '411_alerts_*',
                    'mappings' => [
                        '_default_' => [
                            'properties' => [
                                'alert_date' => ['type' => 'date', 'format' => 'epoch_second'],
                                'assignee_type' => ['type' => 'long'],
                                'assignee' => ['type' => 'long'],
                                'content' => ['type' => 'object'],
                                'search_id' => ['type' => 'long'],
                                'state' => ['type' => 'long'],
                                'resolution' => ['type' => 'long'],
                                'escalated' => ['type' => 'boolean'],
                                'content_hash' => ['type' => 'string'],
                                'notes' => ['type' => 'string'],
                                'create_date' => ['type' => 'date', 'format' => 'epoch_second'],
                                'update_date' => ['type' => 'date', 'format' => 'epoch_second'],
                            ]
                        ]
                    ]
                ]
            ]);
        }

        // Create index.
        if(!$client->indices()->exists(['index' => self::getIndexName()])) {
            $client->indices()->create(['index' => self::getIndexName()]);
        }
    }

    /**
     * Search for Alerts in the index.
     * @param string $query Query string query.
     * @param int $from The lower time threshold.
     * @param int $to The upper time threshold.
     * @param int $offset The offset from the beginning of the result set.
     * @param int $count The number of results to return.
     * @return Alert[] An array of Alerts.
     */
    public function getAlerts($query, $from, $to, $offset, $count) {
        $result_set = $this->query($query, null, $from, $to, false, $offset, $count);

        $ret = [];
        foreach($result_set as $result) {
            foreach($result['hits']['hits'] as $entry) {
                $ret[] = self::format($entry);
            }
        }
        return $ret;
    }

    /**
     * Get a list of Alert ids matching the query.
     * @param string $query Query string query.
     * @param int $from The lower time threshold.
     * @param int $to The upper time threshold.
     * @return int[] An array of Alert ids.
     */
    public function getIds($query, $from, $to) {
        $result_set = $this->query($query, ['id'], $from, $to, true);

        $ret = [];
        foreach($result_set as $result) {
            foreach($result['hits']['hits'] as $entry) {
                $ret[] = $entry['fields']['id'][0];
            }
        }
        return $ret;
    }

    private function query($query, $fields=null, $from=null, $to=null, $scroll=false, $offset=null, $count=null) {
        $client = self::getClient();

        $filter = [];
        $conds = [];
        if(!is_null($from)) {
            $conds['gte'] = $from;
        }
        if(!is_null($to)) {
            $conds['lt'] = $to;
        }
        if(count($conds) > 0) {
            $filter = [
                'range' => [ 'alert_date' => $conds ]
            ];
        }

        $body = [
            'query' => [
                'filtered' => [
                    'query' => [
                        'query_string' => [ 'query' => $query ],
                    ],
                    'filter' => $filter
                ],
            ],
            'sort' => [ 'alert_date' => [ 'order' => 'desc', 'ignore_unmapped' => true ] ]
        ];

        if(!is_null($offset)) {
            $body['from'] = $offset;
        }
        if(!is_null($count)) {
            $body['size'] = $count;
        }
        if(!is_null($fields)) {
            $body['fields'] = $fields;
        }

        $result_set = [];
        try {
            if($scroll) {
                $response = $client->search([
                    'index' => $this->index,
                    'body' => $body,
                    'scroll' => '15s',
                ]);
                $result_set[] = $response;

                do {
                    if(!array_key_exists('_scroll_id', $response)) {
                        throw new ElasticException('No scroll id');
                    }

                    $response = $client->scroll([
                        'scroll_id' => $response['_scroll_id'],
                        'scroll' => '15s'
                    ]);
                    $result_set[] = $response;
                } while(count($response['hits']['hits']) > 0);

                $client->clearScroll(['scroll_id' => $response['_scroll_id']]);
            } else {
                $result_set[] = $client->search([
                    'index' => $this->index,
                    'body' => $body,
                ]);
            }
        } catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            throw new \RuntimeException('Error executing query');
        }

        return $result_set;
    }

    /**
     * Search for Alerts in the index. Return results grouped by several fields.
     * @param string $query Query string query.
     * @param int $from The lower time threshold.
     * @param int $to The upper time threshold.
     * @return array A structure containing Alert information.
     */
    public function bootstrap($query, $from=null, $to=null) {
        $client = self::getClient();

        $fields = ['escalated', 'assignee_type', 'assignee', 'search_id', 'state'];
        $aggs = [];
        $node = &$aggs;
        foreach($fields as $field) {
            $node['aggs'] = ['agg' => ['terms' => [ 'field' => $field, 'size' => 0 ]]];
            $node = &$node['aggs']['agg'];
        }

        $node['aggs'] = ['hits' => [
            'top_hits' => [
                'size' => 10,
                'sort' => [ 'alert_date' => [ 'order' => 'desc', 'ignore_unmapped' => true ] ]
            ]
        ]];

        $filter = [];
        $conds = [];
        if(!is_null($from)) {
            $conds['gte'] = $from;
        }
        if(!is_null($to)) {
            $conds['lt'] = $to;
        }
        if(count($conds) > 0) {
            $filter = [
                'range' => [ 'alert_date' => $conds ]
            ];
        }

        try {
            $data = $client->search([
                'index' => $this->index,
                'body' => [
                    'query' => [
                        'filtered' => [
                            'query' => [
                                'query_string' => [ 'query' => $query ],
                            ],
                            'filter' => $filter
                        ],
                    ],
                    'size' => 0,
                    'aggs' => $aggs['aggs'],
                ]
            ]);
        } catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            throw new \RuntimeException('Error executing query');
        }

        return $this->bootstrapRecurse([], $data['aggregations'], 0, array_reverse($fields));
    }

    private function bootstrapRecurse($node, $data, $count, $fields) {
        $ret = [];

        if(array_key_exists('agg', $data)) {
            $key = array_pop($fields);

            // Iterate over each bucket and continue recursing.
            foreach($data['agg']['buckets'] as $sub_data) {
                $val = $sub_data['key'];

                // Clone fields (each iteration is independent).
                $new_node = $node;
                $new_node[$key] = $val;
                $ret = array_merge($ret, $this->bootstrapRecurse($new_node, $sub_data, $sub_data['doc_count'], $fields));
            }
        } else if(array_key_exists('hits', $data)) {
            return [['count' => $node + ['count' => $count], 'data' => array_map([$this, 'format'], $data['hits']['hits']['hits'])]];
        }

        return $ret;
    }

    /**
     * Get counts of active Alerts grouped by priority.
     * @return array Count data.
     */
    public function getActiveAlertCounts() {
        $client = self::getClient();

        $filter = [
            'terms' => [
                'state' => [Alert::ST_NEW, Alert::ST_INPROG]
            ]
        ];
        $aggs = [
            'st' => [
                'terms' => [ 'field' => 'state' ]
            ],
            'esc' => [
                'terms' => [
                    'field' => 'escalated',
                ],
                'aggs' => ['prio' => [
                    'terms' => [
                        'field' => 'priority'
                    ]
                ]]
            ]
        ];

        try {
            $data = $client->search([
                'index' => $this->index,
                'body' => [
                    'query' => [
                        'filtered' => [
                            'filter' => $filter
                        ],
                    ],
                    'size' => 0,
                    'aggs' => $aggs
                ]
            ]);

            $states = [0, 0];
            foreach($data['aggregations']['st']['buckets'] as $row) {
                if(array_key_exists($row['key'], $states)) {
                    $states[$row['key']] = $row['doc_count'];
                }
            }

            $priorities = [0, 0, 0];
            $escalated = [0];
            foreach($data['aggregations']['esc']['buckets'] as $row) {
                if($row['key'] == 0) {
                    foreach($row['prio']['buckets'] as $sub_row) {
                        if(array_key_exists($sub_row['key'], $priorities)) {
                            $priorities[$sub_row['key']] = $sub_row['doc_count'];
                        }
                    }
                } else {
                    $escalated[0] = $row['doc_count'];
                }
            }
            $data = array_merge($priorities, $escalated, $states);
        } catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            throw new \RuntimeException('Error getting active count data');
        }

        return $data;
    }

    /**
     * Get counts of new Alerts grouped by date.
     * @param int $range The number of days to return data for.
     * @return array Count data.
     */
    public function getAlertActivityCounts($range) {
        $client = self::getClient();

        $filter = [
            'range' => [
                'create_date' => [
                    'lt' => 'now',
                    'gte' => sprintf('now-%dd/d', $range)
                ]
            ]
        ];
        $aggs = ['agg' => [
            'date_histogram' => [
                'field' => 'create_date',
                'interval' => '1d',
                'format' => 'yyyy-MM-dd',
            ]
        ]];

        try {
            $data = $client->search([
                'index' => $this->index,
                'body' => [
                    'query' => [
                        'filtered' => [
                            'filter' => $filter
                        ],
                    ],
                    'size' => 0,
                    'aggs' => $aggs
                ]
            ]);
        } catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            throw new \RuntimeException('Error getting activity count data');
        }

        return array_map(function($x) {
            return [$x['key_as_string'], $x['doc_count']];
        }, $data['aggregations']['agg']['buckets']);
    }

    /**
     * Format result objects from ES.
     * @param array $data Alert data.
     * @return array Formatted Alert data.
     */
    public static function format($data) {
        return $data['_source'];
    }

    /**
     * Register an Alert to be updated.
     * @param Alert $alert The Alert to update.
     */
    public function update(Alert $alert) {
        $this->list[] = [[
            'index' => [
                '_index' => $this->index,
                '_type' => $alert->getSearch()['type'] ?: 'null',
                '_id' => $alert['alert_id'],
            ]
        ], $alert];

        if(count($this->list) > self::BATCH_SIZE) {
            $this->send();
        }
    }

    /**
     * Register an Alert to be deleted.
     * @param Alert $alert The Alert to update.
     */
    public function delete(Alert $alert) {
        $this->list[] = [[
            'delete' => [
                '_index' => $this->index,
                '_type' => $alert['type'],
                '_id' => $alert['alert_id'],
            ]
        ]];

        if(count($this->list) > self::BATCH_SIZE) {
            $this->send();
        }
    }

    /**
     * Finish processing any remaining Alerts.
     */
    public function finalize() {
        $this->send();
    }

    private function send() {
        if(count($this->list) == 0) {
            return;
        }

        $list = [];
        foreach($this->list as $alert_data) {
            // Each entry in the list has at least one element. Pop that into the list.
            $list[] = $alert_data[0];
            // If there's just one element, we're done (it was a delete). Otherwise, insert additional data.
            if(count($alert_data) == 1) {
                continue;
            }

            $search_id = $alert_data[1]['search_id'];
            if(!Util::exists($this->searches, $search_id)) {
                $this->searches[$search_id] = SearchFinder::getById($search_id);
            }
            $list[] = $this->generateAlertData(
                $alert_data[1], Util::get($this->searches, $search_id)
            );
        }

        $client = self::getClient('alerts', true);
        $resp = $client->bulk([
            'body' => $list
        ]);

        $this->list = [];
    }

    private function generateAlertData(Alert $alert, Search $search=null) {
        $data = $alert->toArray();
        $data['content'] = $this->unflatten((array)$data['content']);

        // Populate search data.
        $search_data = [
            'tags' => Util::get($search, 'tags', []),
            'priority' => Util::get($search, 'priority', Search::P_LOW),
            'category' => Util::get($search, 'category', Search::$CATEGORIES['general']),
            'owner' => Util::get($search, 'owner', 0),
        ];

        // Populate note data.
        $alertlogs = AlertLogFinder::getByQuery([
            'alert_id' => $data['id'],
            'action' => [
                ModelFinder::C_NEQ => AlertLog::A_CREATE
        ]]);
        $notes = [];
        foreach($alertlogs as $alertlog) {
            if(strlen($alertlog['note']) > 0) {
                $notes[] = $alertlog['note'];
            }
        }

        return array_merge($data, $search_data, ['notes' => $notes]);
    }

    public function unflatten(array $data) {
        $ret = [];

        foreach($data as $key=>$val) {
            $path = explode('.', $key);
            $last = array_pop($path);
            $node = &$ret;

            foreach($path as $nkey) {
                $node = &$node[$nkey];
            }

            $node[$last] = $val;
        }

        return $ret;
    }
}
