<?php

namespace FOO;

/**
 * Class Dashboard_REST
 * REST endpoint for dashboard data.
 * @package FOO
 */
class Dashboard_REST extends REST {
    public function GET(array $get) {
        $data = [];
        $meta = new DBMeta;

        $func = function($data) {
            return [$data['date'], Util::get($data, 'count', 0)];
        };

        // Get a count of how many Searches are failing.
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE `site_id` = ? AND `archived` = 0 AND `enabled` = ? AND `last_execution_date` = `last_failure_date` AND `last_execution_date` > 0',
            Search::$TABLE
        );
        $data['failing_searches'] = (int)DB::query($sql, [SiteFinder::getCurrentId(), true], DB::VAL);

        // Get a count of active Alerts and their priorities.
        $sql = sprintf('
            SELECT `escalated`, `priority`, `state`, COUNT(*) as `count`
            FROM `%s` AS A INNER JOIN `%s` AS B USING(`search_id`)
            WHERE A.`site_id` = ? AND `state` IN %s AND A.`archived` = 0 AND B.`archived` = 0
            GROUP BY 1, 2, 3
        ', Alert::$TABLE, Search::$TABLE, DB::inPlaceholder(2));
        $ret = [0, 0, 0, 0,  0, 0];
        foreach(DB::query($sql, [SiteFinder::getCurrentId(), Alert::ST_NEW, Alert::ST_INPROG]) as $count) {
            if($count['escalated']) {
                $ret[3] += $count['count'];
            } else {
                $ret[$count['priority']] += (int)$count['count'];
            }
            $ret[4 + $count['state']] += (int)$count['count'];
        }
        $data['active_alerts_status'] = [];
        $data['active_alerts'] = $ret;

        // Get a count of stale Alerts.
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE `site_id` = ? AND `archived` = 0 AND `update_date` < ? AND `state` IN %s',
            Alert::$TABLE, DB::inPlaceholder(2)
        );
        $data['stale_alerts'] = (int)DB::query($sql, [SiteFinder::getCurrentId(), $_SERVER['REQUEST_TIME'] - (60 * 60 * 24 * 7), Alert::ST_NEW, Alert::ST_INPROG], DB::VAL);

        $range = -10;
        $dates = $this->dateRange($range);

        // Generate data for an Alert creation histogram.
        $sql = sprintf('
            SELECT DATE(create_date, "unixepoch") AS `date`, COUNT(*) AS `count`
            FROM `%s` WHERE `site_id` = ? AND `archived` = 0
            AND DATE(create_date, "unixepoch") > DATE(?, "unixepoch")
            GROUP BY 1
        ', Alert::$TABLE);
        $ret = DB::query($sql, [SiteFinder::getCurrentId(), strtotime("now $range days")]);
        $data['historical_alerts'] = $this->fillDates($ret, $dates, $func);

        // Generate data for an Alert action histogram.
        $sql = sprintf('
            SELECT DATE(create_date, "unixepoch") as `date`, `action`, COUNT(*) as `count` FROM `%s` INNER JOIN (
                SELECT DATE(create_date, "unixepoch"), `alert_id`, MAX(`create_date`) as `create_date`
                FROM `%s` WHERE `site_id` = ? AND `archived` = 0 AND (
                    (`action` = ? AND `a` = 1) OR
                    (`action` = ? AND (`a` != 0 OR `b` != 0)) OR
                    (`action` = ? AND `a` = 2)
                ) AND DATE(create_date, "unixepoch") > DATE(?, "unixepoch") GROUP BY 1, 2
            ) AS `tbl` USING(`alert_id`, `create_date`) GROUP BY 1, 2;
        ', AlertLog::$TABLE, AlertLog::$TABLE);
        $ret = DB::query($sql, [SiteFinder::getCurrentId(), AlertLog::A_ESCALATE, AlertLog::A_ASSIGN, AlertLog::A_SWITCH, strtotime("now $range days")]);
        $groups = [[], [], []];
        foreach($ret as $row) {
            switch($row['action']) {
                case AlertLog::A_ESCALATE:
                    $groups[0][] = $row;
                    break;
                case AlertLog::A_ASSIGN: // Assign
                    $groups[1][] = $row;
                    break;
                case AlertLog::A_SWITCH:
                    $groups[2][] = $row;
                    break;
            }
        }
        $data['historical_actions'] = [
            $this->fillDates($groups[0], $dates, $func),
            $this->fillDates($groups[1], $dates, $func),
            $this->fillDates($groups[2], $dates, $func),
        ];

        // Cron hasn't run in more than 20 minutes.
        $data['no_recent_cron'] = (int) $meta['last_cron_date'] < ($_SERVER['REQUEST_TIME'] - 20 * 60);

        return self::format($data);
    }

    private function dateRange($delta=-20) {
        $start = $delta > 0 ? 0:$delta;
        $end = $delta > 0 ? $delta:0;
        return array_map(function($x) { return date('Y-m-d', strtotime("now $x days")); }, range($start, $end));
    }

    private function fillDates($data, $range, $func) {
        $i = 0;
        $ret = [];
        foreach($range as $date) {
            if($i < count($data) && $data[$i]['date'] == $date) {
                $ret[] = $func($data[$i]);
                ++$i;
            } else {
                $ret[] = $func(['date' => $date]);
            }
        }

        return $ret;
    }
}
