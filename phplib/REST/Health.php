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
            $sources = $MODEL::getSources();
            if(is_null($sources)) {
                $search_health[$MODEL::$TYPE] = $this->isWorking(new $MODEL);
            } else {
                foreach($sources as $source) {
                    $model = new $MODEL;
                    $model['source'] = $source;
                    $key = sprintf('%s[%s]', $MODEL::$TYPE, $source);
                    $search_health[$key] = $this->isWorking($model);
                }
            }
        }

        $ret = [
            'cron_enabled' => (bool) $cfg['cron_enabled'],
            'last_cron_date' => (int) $meta['last_cron_date'],
            'search_health' => $search_health,
            'searchjob_health' => JobFinder::getCounts(),
        ];

        return self::format($ret);
    }

    private function isWorking($search) {
        $working = false;
        try {
            $working = $search->isWorking($_SERVER['REQUEST_TIME']);
        } catch(\Exception $e) {}

        return $working;
    }
}
