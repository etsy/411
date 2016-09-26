<?php

namespace FOO;

/**
 * Class Push_Search
 * Search stub for pushing Alerts into 411.
 * @package FOO
 */
class Push_Search extends Search {
    public static $TYPE = 'push';

    /** @var array[] An array of results to pass through. */
    private $results = [];

    protected function constructQuery() {
        return null;
    }

    public function shouldRun($date, $backfill = false) {
        return false;
    }

    public function setResults(array $results) {
        $this->results = $results;
    }

    protected function _execute($date, $constructed_qdata) {
        $alerts = [];

        foreach($this->results as $result) {
            $alert = new Alert;
            $alert['alert_date'] = (int) Util::get($alert, 'alert_date', $_SERVER['REQUEST_TIME']);
            $alert['content'] = (array) Util::get($alert, 'content', []);

            $alerts[] = $alert;
        }
        $this->results = [];

        return $alerts;
    }
}
