<?php

namespace FOO;

/**
 * Class Health_REST
 * REST endpoint for retrieving health information.
 * @package FOO
 */
class Health_REST extends REST {
    public function GET(array $get) {
        $cfg = new DBConfig();
        $meta = new DBMeta();

        $search_health = [];
        foreach(Search::getTypes() as $MODEL) {
            $search = new $MODEL;
            $working = false;
            try {
                $working = $search->isWorking($_SERVER['REQUEST_TIME']);
            } catch(\Exception $e) {}
            $search_health[$MODEL::$TYPE] = $working;
        }

        $ret = [
            'cron_enabled' => (bool) $cfg['cron_enabled'],
            'last_cron_date' => (int) $meta['last_cron_date'],
            'search_health' => $search_health,
            'searchjob_health' => JobFinder::getCounts(),
        ];

        return self::format($ret);
    }
}
