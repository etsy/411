<?php

namespace FOO;

/**
 * Class Rollup_Job
 * Represents a scheduled execution of a Rollup.
 * @package FOO
 */
class Rollup_Job extends Job {
    public static $TYPE = 'rollup';

    const I_HOURLY = 0;
    const I_DAILY = 1;

    /**
     * Process rollups for a site and send out emails.
     * @return array null, array of errors and whether failures are ignorable.
     */
    public function run() {
        switch($this->obj['target_id']) {
        case static::I_HOURLY:
            $type = Search::NT_HOURLY;
            $range = 1 * 60 * 60;
            break;
        case static::I_DAILY:
            $type = Search::NT_DAILY;
            $range = 24 * 60 * 60;
            break;
        default:
            throw new JobCancelException('Invalid rollup job type');
        }

        // Grab a list of Searches.
        $searches = SearchFinder::getByQuery([
            'notif_type' => $type
        ]);

        // Group by assignee.
        $alert_groups = [[], []];

        // Process each Search.
        for($i = 0; $i < count($searches); ++$i) {
            $search = $searches[$i];

            // Find and process all Alerts.
            $alerts = AlertFinder::getByQuery([
                'search_id' => $search['search_id'],
                'create_date' => [
                    ModelFinder::C_GTE => $this->obj['target_date'] - $range,
                    ModelFinder::C_LT => $this->obj['target_date'],
            ]]);
            foreach($alerts as $alert) {
                $assignee_type = $alert['assignee_type'];
                $assignee = $alert['assignee'];

                // Each group contains: new Alerts, new Actions + associated Alerts
                if(!Util::exists($alert_groups[$assignee_type], $assignee)) {
                    $alert_groups[$assignee_type][$assignee] = [[], [], []];
                }

                $alert_groups[$assignee_type][$assignee][0][] = $alert;
            }

            // Find and process all AlertLogs.
            $alertlogs = AlertLogFinder::getRecent($this->obj['target_date'], $type, $range);
            foreach($alertlogs as $alertlog) {
                $alert = AlertFinder::getById($alertlog['alert_id']);
                $assignee_type = $alert['assignee_type'];
                $assignee = $alert['assignee'];

                // Each group contains: new Alerts, new Actions + associated Alerts
                if(!Util::exists($alert_groups[$assignee_type], $assignee)) {
                    $alert_groups[$assignee_type][$assignee] = [[], [], []];
                }

                $alert_groups[$assignee_type][$assignee][1][] = $alertlog;
                $alert_groups[$assignee_type][$assignee][2][] = $alert;
            }
        }

        // Send out email notifications.
        foreach($alert_groups as $assignee_type=>$alert_group) {
            foreach($alert_group as $assignee=>$data) {
                // Send.
                $to = Assignee::getEmails($assignee_type, $assignee);
                Notification::sendRollupEmail(
                    $to,
                    $data[0], $data[1],
                    $searches,
                    $data[2],
                    AlertFinder::getActiveCounts(),
                    false,
                    $this->getDebugData()
                );
            }
        }

        return [null, [], false];
    }
}
