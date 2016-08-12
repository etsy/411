<?php

namespace ESQuery;

/**
 * Class Engine
 * Coordinates parsing, querying and processing.
 */
class Engine {
    /** @var array Query settings. */
    private $settings = null;
    /** @var array List of queries. */
    private $query_list = null;
    /** @var callable|null Connection provider. */
    private $conn_provider = null;
    /** @var callable|null List provider. */
    private $list_provider = null;

    /**
     * Construct a new Query object
     * @param string $query_str Query to execute.
     * @param array $addn_settings Additional settings to apply.
     * @param callable $conn_provider Connection provider.
     * @param callable $list_provider List provider.
     */
    public function __construct($query_str, $addn_settings=[], $conn_provider=null, $list_provider=null) {
        $parser = new Parser();
        list($settings, $query_list) = $parser->parse($query_str);

        $this->settings = array_merge($settings, $addn_settings);
        $this->query_list = $query_list;
        $this->conn_provider = $conn_provider;
        $this->list_provider = $list_provider;
    }

    /**
     * Execute the query.
     * @return Results.
     */
    public function execute() {
        $sch = new Scheduler($this->settings, $this->query_list, $this->conn_provider, $this->list_provider);
        return $sch->execute();
    }
}
