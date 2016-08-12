<?php

namespace ESQuery;

/**
 * Class Result.
 * Represents an Elasticsearch request along with its associated result set
 * Accepts a settings array with the following keys:
 *
 * Per query:
 *  host - Host name of the host to query. Passed through to the ES client library. [localhost:9200]
 *  to - To date when querying. [time()]
 *  from - From date when querying. [time()]
 *  size - Max result size. [100]
 *  allow_leading_wildcard - Whether to allow leading wildcards in the query [false]
 *  index - Base name of the index to query on. []
 *  date_field - The date field to query on. []
 *  date_based - Whether the index is date based. [false]
 *
 * Global:
 *  fields - Fields to return. []
 *  map - Fields to rename. []
 *  flatten - Whether to flatten arrays. [true]
 *  sort - Fields to sort the results on. []
 *  count - Return a count of results. [false]
 */
class Result implements \JsonSerializable {
    /** @var \Elasticsearch\Elasticsearch Client object. */
    private $client = null;
    /** @var Result Source query. */
    private $source = null;
    /** @var Query structure. */
    private $query = null;
    /** @var array List of aggs structures. */
    private $aggs = [];
    /** @var array Post query structure. */
    private $post_query = null;
    /** @var array List of settings to apply. */
    private $settings = [];
    /** @var callable|null Connection provider. */
    private $conn_provider = null;
    /** @var callable|null List provider. */
    private $list_provider = null;

    /** @var $query Query structure. */
    public function __construct($query=null) {
        if(!is_null($query)) {
            $this->setQuery($query);
        }
    }

    public function setSource($source) {
        $this->source = $source;
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    public function addAgg($agg) {
        $this->aggs[] = $agg;
    }

    public function setPostQuery($post_query) {
        $this->post_query = $post_query;
    }

    public function setSettings($settings) {
        $this->settings = $settings;
    }

    public function setConnProvider($conn_provider) {
        $this->conn_provider = $conn_provider;
    }

    public function setListProvider($list_provider) {
        $this->list_provider = $list_provider;
    }

    /**
     * Process the query and return the results.
     */
    public function execute() {
        if(is_null($this->client)) {
            $this->client = $this->getConnection();
        }
        if(is_null($this->query)) {
            throw new ElasticException('No query');
        }

        list($query_data, $meta) = $this->constructQuery($this->source, $this->query, $this->aggs, $this->post_query, $this->settings);

        $results_set = $this->query($query_data, $meta);
        $results = $this->processResults($results_set, $meta);
        $results = $this->postProcessResults($this->post_query, $results, $meta);

        return $results;
    }

    /**
     * Send the query off to Elasticsearch and get the raw results back.
     */
    public function query($query_data, $meta) {
        $result_set = [];

        if($meta['scroll']) {
            $query_data['scroll'] = '15s';
            $response = $this->client->search($query_data);

            $state = [];
            do {
                if(!array_key_exists('_scroll_id', $response)) {
                    throw new ElasticException('No scroll id');
                }

                $response = $this->client->scroll([
                    'scroll_id' => $response['_scroll_id'],
                    'scroll' => '15s'
                ]);
                $result_set[] = $response;
            } while(count($response['hits']['hits']) > 0);

            $this->client->clearScroll(['scroll_id' => $response['_scroll_id']]);
        } else {
            $result_set[] = $this->client->search($query_data);
        }

        return $result_set;
    }

    /**
     * Output the query as a json string.
     */
    public function jsonSerialize() {
        list($query_data, $meta) = $this->constructQuery($this->source, $this->query, $this->aggs, $this->post_query, $this->settings);

        return $query_data;
    }

    /**
     * Construct the query portion of the request body.
     */
    private function constructQuery($source, $query, $aggs, $post_query, $settings) {
        $query_data = [
            'ignore_unavailable' => true,
        ];
        $query_body = [
            'size' => 100,
        ];
        $meta = [
            'aggs' => [],
            'flatten' => true,
            'map' => [],
            'scroll' => false,
            'post_query' => false,
            'count' => false,
        ];

        $from = Util::get($settings, 'from', time());
        $to = Util::get($settings, 'to', time());
        $date_field = Util::get($settings, 'date_field');

        // Construct filter query clauses.
        $filter_data = $this->constructFilter($source, $query, $date_field, $from, $to);
        if(!is_null($filter_data)) {
            $query_body['query'] = [
                'filtered' => [
                    'filter' => $filter_data
                ]
            ];
        }

        // Set index.
        if(Util::exists($settings, 'index')) {
            $query_data['index'] = $settings['index'];

            if(Util::get($settings, 'date_based', false)) {
                $index = $query_data['index'];

                $query_data['index'] = implode(',', array_map(function($x) use ($index) {
                    return "$index-$x";
                }, Util::getIndices($from, $to)));
            }
        }

        // Construct agg queries clauses.
        $agg_data = $this->constructAggs($aggs);

        // Construct setting data.
        if(!is_null($agg_data)) {
            $query_body['aggs'] = $agg_data;
            // When executing an agg, we don't care about the actual hits.
            $query_body['size'] = 0;
            $meta['aggs'] = $aggs;
        }
        if(array_key_exists('count', $settings)) {
            $meta['count'] = (bool) $settings['count'];

            // Same thing if we want a count and have no aggs or post query.
            if(is_null($post_query)) {
                $query_body['size'] = 0;
            }
        }
        if(array_key_exists('flatten', $settings)) {
            $meta['flatten'] = (bool) $settings['flatten'];
        }
        if(array_key_exists('map', $settings)) {
            $meta['map'] = (array) $settings['map'];
        }
        if(!is_null($post_query)) {
            $meta['post_query'] = true;
        }
        // Determine whether to use a scroll query, which is more efficient.
        /*
        if(...) {
            $meta['scroll'] = true;
        }
        */

        // Construct setting clauses.
        $query_body = array_merge($query_body, $this->constructSettings($settings));

        $query_data['body'] = $query_body;

        return [$query_data, $meta];
    }

    /**
     * Construct the filters within the query.
     */
    private function constructFilter($source, $query, $date_field, $from, $to) {
        if(is_null($query)) {
            return null;
        }
        if($query[0] !== Token::C_SEARCH && is_null($source)) {
            throw new ElasticException('No source data for query');
        }

        // There are 2 types of queries. Process each one separately.
        $filters = [];
        switch($query[0]) {
            case Token::C_SEARCH:
                $filters = $query[1];
                break;
            case Token::C_JOIN:
                $filters = $query[3];
                $terms = [];
                foreach($source as $row) {
                    $terms[] = $row[$query[1]];
                }
                $filter = [Token::X_LIST, $query[2], $terms, false];

                // AND the filter with the rest of the filters (if they exist).
                if(count($filters)) {
                    $filters = [Token::F_AND, [$filters, $filter]];
                } else {
                    $filters = $filter;
                }
                break;
            default:
                throw new ElasticException('Unexpected query type');
        }

        // Add time range filter.
        if(!is_null($date_field)) {
            $filter = [Token::F_RANGE, $date_field, true, false, $from, $to];
            if(count($filters)) {
                $filters = [Token::F_AND, [$filters, $filter]];
            } else {
                $filters = $filter;
            }
        }

        return $this->constructFilterRecurse($filters);
    }

    private function constructFilterRecurse($node) {
        if(!count($node)) {
            return [];
        }

        switch($node[0]) {
            case Token::F_AND:
                $list = [];
                foreach($node[1] as $c_node) {
                    $list[] = $this->constructFilterRecurse($c_node);
                }
                return ['and' => $list];

            case Token::F_OR:
                $list = [];
                foreach($node[1] as $c_node) {
                    $list[] = $this->constructFilterRecurse($c_node);
                }
                return ['or' => $list];

            case Token::F_NOT:
                return ['not' => $this->constructFilterRecurse($node[1])];

//            case Token::F_IDS:

            case Token::F_EXISTS:
                return ['exists' => ['field' => $node[1]]];

            case Token::F_MISSING:
                return ['missing' => ['field' => $node[1]]];

//            case Token::F_QUERY:

            case Token::F_RANGE:
                $lo = $node[2] ? 'gte':'gt';
                $hi = $node[3] ? 'lte':'lt';
                $range = [
                    'format' => 'strict_date_optional_time||epoch_second'
                ];
                if(!is_null($node[4])) {
                    $range[$lo] = $node[4];
                }
                if(!is_null($node[5])) {
                    $range[$hi] = $node[5];
                }
                return ['range' => [$node[1] => $range]];

            case Token::F_TERM:
                return ['term' => [$node[1] => $node[2]]];

            case Token::F_TERMS:
                return ['terms'=> [$node[1] => $node[2]]];

            case Token::F_REGEX:
                return ['regexp' => [$node[1] => $node[2]]];

            case Token::F_PREFIX:
                return ['prefix' => [$node[1] => $node[2]]];

            case Token::Q_QUERYSTRING:
                return ['query' => [
                    'query_string' => [
                        'default_field' => $node[1],
                        'query' => Util::escapeGroup($node[2]),
                        'default_operator' => 'AND'
                    ]
                ]];

            case Token::X_LIST:
                $arr = is_array($node[2]) ?
                    $node[2]:$this->getList($node[2]);

                return $this->processList($node[1], $arr, $node[3]);

            default:
                throw new ElasticException('Unknown filter type');
        }
    }

    /**
     * Call the list provider to retrieve the contents of the list.
     * @param string $key List name.
     * @return array An array of entries.
     */
    private function getList($key) {
        return !is_null($this->list_provider) ? call_user_func($this->list_provider, $key):[];
    }

    public function getConnection() {
        $host = Util::get($this->settings, 'host');
        if(!is_null($this->conn_provider)) {
            return call_user_func($this->conn_provider, $host);
        } else {
            $cb = \Elasticsearch\ClientBuilder::create();
            if(!is_null($host)) {
                $cb->setHosts([$host]);
            }
            return $cb->build();
        }
    }

    private function processList($field, $arr, $inline) {
        if($inline) {
            if(count($arr) > 1000) {
                throw new ElasticException('Too many entries in list');
            }
            return ['query' => [
                'query_string' => [
                    'default_field' => $field,
                    'query' => implode(' OR ', array_map(['\ESQuery\Util', 'escapeGroup'], $arr))
                ]
            ]];
        } else {
            // Generate a lookup table.
            $doc = [
                'table' => $arr
            ];
            $id = time() . '_' . rand();

            $response = $this->client->index([
                'index' => 'lookup_tables',
                'type' => 'esq_lookup',
                'id' => $id,
                'ttl' => '1m',
                'body' => $doc
            ]);

            return ['terms'=> [$field => [
                'index' => 'lookup_tables',
                'type' => 'esq_lookup',
                'id' => $id,
                'path' => 'table'
            ]]];
        }
    }

    /**
     * Generate a list of aggregations to request from Elasticsearch.
     */
    private function constructAggs($queries) {
        if(!count($queries)) {
            return null;
        }

        return $this->constructAggsRecurse($queries, 0);
    }

    private function constructAggsRecurse($queries, $i) {
        $query = $queries[$i];
        $agg = null;
        switch($query[1]) {
            case Token::A_TERMS:
                 $agg = ['terms' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_SIGTERMS:
                $agg = ['significant_terms' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_CARD:
                $agg = ['cardinality' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_MIN:
                $agg = ['min' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_MAX:
                $agg = ['max' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_AVG:
                $agg = ['avg' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            case Token::A_SUM:
                $agg = ['sum' => array_merge($query[3], ['field' => $query[2]])];
                 break;

            default:
                throw new ElasticException('Unknown agg type');
        }

        if($i + 1 < count($queries)) {
            $agg['aggs'] = $this->constructAggsRecurse($queries, $i + 1);
        }
        $ret = ['$$$_' . $query[2] => $agg];

        return $ret;
    }

    /**
     * Construct settings for the request body.
     */
    private function constructSettings($settings) {
        $ret = [];

        if(array_key_exists('fields', $settings)) {
            $ret['_source'] = ['include' => $settings['fields']];
        }

        if(array_key_exists('sort', $settings)) {
            $ret['sort'] = array_map(function($x) { return [$x[0] => ['order' => $x[1] ? 'asc':'desc']]; }, $settings['sort']);
        }

        $valid_keys = ['size', 'allow_leading_wildcard'];
        foreach($valid_keys as $key) {
            if(array_key_exists($key, $settings)) {
                $ret[$key] = $settings[$key];
            }
        }

        return $ret;
    }

    /**
     * Process raw results and return parsed results.
     */
    private function processResults($response_set, $meta) {
        // If getting an aggregation, results appear on the first response.
        if(count($meta['aggs']) > 0) {
            return $this->processAggResults($response_set[0], $meta);
        }

        // If we're only returning hits, we can return the count here.
        if($meta['count'] && !$meta['post_query']) {
            return ['count' => array_sum(array_map(function($x) { return $x['hits']['total']; }, $response_set))];
        }

        $results = [];
        $this->processHitResults($results, $response_set, $meta);
        return $results;
    }

    /**
     * Process any aggregation results recursively.
     */
    private function processAggResults($response, $meta) {
        $aggs = $response['aggregations'];
        if(!count($aggs)) {
            throw new ElasticException('No keys in aggregation');
        }
        $ret = $this->processAggResultsRecurse(null, $aggs, $meta['aggs'], 0);

        // If we don't have a post query, we can return the count here.
        if($meta['count'] && !$meta['post_query']) {
            $ret = ['count' => count($ret)];
        }

        return $ret;
    }

    /**
     * Process any hit results.
     */
    private function processHitResults(&$results, $response_set, $meta) {
        // Determine what fields we want to rename.
        $fields = null;
        if(count($meta['map'])) {
            $fields = $meta['map'];
        }

        foreach($response_set as $response) {
            foreach($response['hits']['hits'] as $result) {
                $data = $result['_source'];
                if($fields) {
                    // Map fields.
                    foreach($fields as $old_field=>$new_field) {
                        if(array_key_exists($old_field, $data)) {
                            $data[$new_field] = $data[$old_field];
                            unset($data[$old_field]);
                        }
                    }
                }

                unset($result['_source']);
                $result = array_merge($result, $data);
                if($meta['flatten']) {
                    $result = $this->processHitResultsRecurse($result);
                }

                $results[] = $result;
            }
        }
    }

    /**
     * Process any hit results recursively.
     */
    private function processHitResultsRecurse($results, $prefix=null) {
        if(!is_array($results)) {
            return [$prefix => $results];
        }

        $ret = [];
        foreach($results as $key=>$result) {
            // Flatten arrays.
            $sub_prefix = is_null($prefix) ? $key:"$prefix.$key";
            $ret = array_merge($ret, $this->processHitResultsRecurse($result, $sub_prefix));
        }
        return $ret;
    }

    /**
     * Process any aggregation results recursively.
     */
    private function processAggResultsRecurse($pkey, $node, $queries, $i) {
        // If terminal node, return the actual value.
        if($i >= count($queries)) {
            return [[$pkey => $node['key'], 'count' => $node['doc_count']]];
        }

        $ret = [];
        $pvalue = array_key_exists('key', $node) ? $node['key']:null;
        $key = $queries[$i][2];
        $node = $node['$$$_' . $key];
        $head = [];
        if(!is_null($pkey)) {
            $head = [$pkey => $pvalue];
        }

        if(!array_key_exists('buckets', $node)) {
            $ret[] = array_merge($head, $node);
        } else {
            $buckets = $node['buckets'];
            foreach($buckets as $bucket) {
                $data = $this->processAggResultsRecurse($key, $bucket, $queries, $i + 1);
                foreach($data as $row) {
                    $ret[] = array_merge($row, $head);
                }
            }
        }
        return $ret;
    }

    /**
     * Run any post-processing steps on the results.
     */
    private function postProcessResults($post_query, $results, $meta) {
        if(is_null($post_query)) {
            return $results;
        }

        $ret = [];
        switch($post_query[0]) {
            // Join up all results with a given key:value.
            case Token::C_TRANS:
                $key = $post_query[1];

                $ret_map = [];
                foreach($results as $result) {
                    if(!array_key_exists($key, $result)) {
                        continue;
                    }

                    $value = $result[$key];
                    if(!array_key_exists($value, $ret_map)) {
                        $ret_map[$value] = [];
                    }
                    $ret_map[$value] = array_merge($ret_map[$value], $result);
                }

                $ret = array_values($ret_map);
                break;
            default:
                throw new ElasticException('Unexpected command');
        }

        // If we wanted a count, generate it now.
        if($meta['count']) {
            $ret = ['count' => count($ret)];
        }

        return $ret;
    }
}
