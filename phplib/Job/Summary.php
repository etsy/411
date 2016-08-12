<?php

namespace FOO;

/**
 * Class Summary_Job
 * Represents a scheduled generation of a weekly summary.
 * @package FOO
 */
class Summary_Job extends Job {
    public static $TYPE = 'summary';

    const NUM_RES = 5;

    /**
     * Generate and send a summary email for the week.
     * @return array null and an array of errors.
     */
    public function run() {
        $cfg = new DBConfig;
        $dt = new \DateTime('@' . $this->obj['target_date']);
        $dt->setTime(0, 0, 0);
        $end_ts = $dt->getTimestamp();
        $dt->modify('-7 days');
        $start_ts = $dt->getTimestamp();

        // New Alerts this week.
        $new_count = AlertFinder::countByQuery(['create_date' => [
            ModelFinder::C_GTE => $start_ts,
            ModelFinder::C_LT => $end_ts
        ]]);

        // Closed Alerts this week.
        $close_count = AlertLogFinder::countByQuery([
            'action' => AlertLog::A_SWITCH,
            'a' => Alert::ST_RES,
            'create_date' => [
                ModelFinder::C_GTE => $start_ts,
                ModelFinder::C_LT => $end_ts
            ]
        ]);

        $leaders = AlertLogFinder::getRecentResolveCounts($start_ts, $end_ts, self::NUM_RES);

        $open_count = AlertFinder::countByQuery(['state' => [Alert::ST_NEW, Alert::ST_INPROG]]);

        $search_counts = AlertFinder::getRecentSearchCounts($start_ts, $end_ts);
        $noisy_searches = array_slice($search_counts, 0, self::NUM_RES);
        $quiet_searches = array_slice($search_counts, -self::NUM_RES);

        // Send.
        Notification::sendSummaryEmail(
            $cfg['default_email'],
            new \DateTime("@$start_ts"),
            [$new_count, $close_count, $open_count],
            $this->mapUsers($leaders),
            $this->mapSearches($noisy_searches),
            $this->mapSearches($quiet_searches)
        );

        return [null, []];
    }

    /**
     * Populate Search objects into a list of counts.
     * @param array $search_counts The list of Search ids and counts.
     * @return array A list of Search objects and counts.
     */
    private function mapSearches($search_counts) {
        return array_map(function($x) {
            return [SearchFinder::getById($x['search_id']), $x['count']];
        }, $search_counts);
    }

    /**
     * Populate User objects into a list of counts.
     * @param array $user_counts The list of User ids and counts.
     * @return array A list of User objects and counts.
     */
    private function mapUsers($user_counts) {
        return array_map(function($x) {
            return [UserFinder::getById($x['user_id']), $x['count']];
        }, $user_counts);
    }
}
