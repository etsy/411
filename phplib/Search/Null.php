<?php

namespace FOO;

/**
 * Class Null_Search
 * Noop.
 * @package FOO
 */
class Null_Search extends Search {
    public static $TYPE = 'null';

    protected function constructQuery() {
        return null;
    }

    protected function _execute($date, $constructed_qdata) {
        $alert = new Alert();
        $alert['alert_date'] = $date;
        $alert['content'] = [
            'null' => 'null'
        ];
        return [$alert];
    }
}
