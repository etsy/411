<?php

namespace FOO;

/**
 * Class Autoclose_Job
 * Represents a scheduled job to close stale Alerts.
 * @package FOO
 */
class Autoclose_Job extends Job {
    public static $TYPE = 'autoclose';

    /**
     * Process any Alerts that are stale.
     * @return array null, array of errors and whether failures are ignorable.
     */
    public function run() {
        // Grab a list of jobs that have autoclosing enabled.
        $searches = SearchFinder::getByQuery(['autoclose_threshold' => [
            ModelFinder::C_GT => 0
        ]]);

        // Grab an ESClient instance so we can update Alerts in the index.
        $client = new ESClient;

        for($i = 0; $i < count($searches); ++$i) {
            $search = $searches[$i];

            // Grab a list of Alerts that should be closed.
            $alerts = AlertFinder::getByQuery([
                'search_id' => $search['search_id'],
                'state' => [Alert::ST_NEW, Alert::ST_INPROG],
                'update_date' => [
                    ModelFinder::C_LT => $this->obj['target_date'] - ($search['autoclose_threshold'])
                ]
            ]);

            foreach($alerts as $alert) {
                $alert['state'] = Alert::ST_RES;
                $alert->store();

                $log = new AlertLog();
                $log['alert_id'] = $alert['id'];
                $log['note'] = 'Autoclosed';
                $log['action'] = AlertLog::A_SWITCH;
                $log['a'] = Alert::ST_RES;
                $log['b'] = Alert::RES_OLD;
                $log->store();

                $client->update($alert);
            }

            $this->setCompletion((($i + 1) / count($searches)) * 100);
        }

        $client->finalize();

        return [null, [], false];
    }
}
