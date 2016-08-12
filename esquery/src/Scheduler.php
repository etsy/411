<?php

namespace ESQuery;

/**
 * Class Scheduler
 * Schedules and runs all the queries.
 */
class Scheduler {
    /** @var Result[] A list of results. */
    private $results = null;
    /** @var callable|null Connection provider. */
    private $conn_provider = null;
    /** @var callable|null List provider. */
    private $list_provider = null;

    /**
     * @param array $settings Query settings.
     * @param array $query_list List of queries.
     */
    public function __construct($settings, $query_list, $conn_provider=null, $list_provider=null) {
        $this->conn_provider = $conn_provider;
        $this->list_provider = $list_provider;

        if(!is_null($query_list) || !is_null($settings)) {
            $this->results = $this->generateResults($settings, $query_list);
        }
    }

    /**
     * Execute the query.
     * @return Results.
     */
    public function execute() {
        $curr = null;
        foreach($this->results as $result) {
            if(!is_null($curr)) {
                $result->setSource($curr);
            }

            $curr = $result->execute();
        }

        return $curr;
    }

    /**
     * Generate result objects for processing.
     * @param array $settings Query settings.
     * @param array $query_list List of queries.
     * @return An array of result objects.
     */
    private function generateResults($settings, $query_list) {
        if(count($query_list) == 0) {
            return [];
        }

        $query_stack = array_reverse($query_list);
        $results = [];

        // Extract global settings. These are applied to all results.
        $global_settings = [];
        foreach($settings as $k=>$v) {
            if(!in_array($k, ['fields', 'map', 'flatten', 'sort', 'count'])) {
                $global_settings[$k] = $v;
            }
        }

        // Construct result objects from our list of querys and
        // link them together.
        do {
            $curr = new Result();
            $curr->setConnProvider($this->conn_provider);
            $curr->setListProvider($this->list_provider);
            $curr->setSettings($global_settings);

            // A source has been set.
            $source = false;
            // We've consumed at least one agg.
            $agg = false;
            // We've consumed a metrics agg.
            $metrics = false;
            // We've consumed all the commands we can.
            $end = false;
            while(count($query_stack) && !$end) {
                $len = count($query_stack);
                switch($query_stack[$len - 1][0]) {
                    // Transactions are terminal commands.
                    case Token::C_TRANS:
                        // It doesn't make sense for a transaction to be followed by anything.
                        if(count($query_stack) > 1) {
                            throw new Exception('Unexpected command');
                        }
                        $curr->setPostQuery(array_pop($query_stack));
                        $end = true;
                        break;
                    // Consume a source command and enable the source bit.
                    case Token::C_SEARCH:
                    case Token::C_JOIN:
                        if(!$source) {
                            $curr->setQuery(array_pop($query_stack));
                            $source = true;
                        } else {
                            $end = true;
                        }
                        break;

                    // Consume an agg if we've already a source.
                    case Token::C_AGG:
                        if(!$source) {
                            throw new Exception('No source');
                        }
                        $q = array_pop($query_stack);
                        // Metrics aggs can't have children.
                        switch($q[1]) {
                            case Token::A_MIN:
                            case Token::A_MAX:
                            case Token::A_AVG:
                            case Token::A_SUM:
                                if($metrics) {
                                    throw new Exception('Unexpected agg');
                                }
                                $metrics = true;
                        }
                        $curr->addAgg($q);
                        $agg = true;
                        break;

                    default:
                        throw new Exception('Unknown command type');
                }
            }

            // Register providers and add to the list.
            $results[] = $curr;
        } while(count($query_stack));

        $results[count($results) - 1]->setSettings($settings);

        return $results;
    }
}
