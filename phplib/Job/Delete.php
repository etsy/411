<?php

namespace FOO;

/**
 * Class Delete_Job
 * Represents a scheduled job to delete alerts from the ES index.
 * @package FOO
 */
class Delete_Job extends Job {
    public static $TYPE = 'delete';

    const CHUNK_SIZE = 1000;

    /**
     * Delete alerts
     * @return array null, array of errors and whether failures are ignorable.
     */
    public function run() {
        $client = new ESClient;

        $id = 0;
        $curr = 0;
        $target = $this->obj['target_id'];
        $query = ['search_id' => $target];
        $count = AlertFinder::countByQuery($query);

        do {
            $query['alert_id'] = [ModelFinder::C_GT => $id];
            $alerts = AlertFinder::getByQuery($query, self::CHUNK_SIZE, 0, [['alert_id', ModelFinder::O_ASC]]);

            foreach($alerts as $alert) {
                $id = $alert['alert_id'];
                $client->delete($alert);
            }

            $curr += count($alerts);
            $this->setCompletion($count > 0 ? (($curr * 100) / $count):100);
        } while(count($alerts) > 0 && $curr < $count);

        $client->finalize();

        return [null, [], false];
    }
}
