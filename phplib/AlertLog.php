<?php

namespace FOO;

/**
 * Class AlertLog
 * Represents an action on an Alert.
 * @package FOO
 */
class AlertLog extends Model {
    public static $TABLE = 'alert_logs';
    public static $PKEY = 'log_id';

    // Actions.
    /** Noop action */
    const A_NONE = 0;
    /** Create action. */
    const A_CREATE = 1;
    /** Escalation action */
    const A_ESCALATE = 4;
    /** Assignment action */
    const A_ASSIGN = 5;
    /** State change action */
    const A_SWITCH = 6;
    /** Note action */
    const A_NOTE = 7;

    protected static function generateSchema() {
        return [
            'user_id' => [static::T_NUM, null, User::NONE],
            'alert_id' => [static::T_NUM, null, 0],
            'note' => [static::T_STR, null, ''],
            'action' => [static::T_NUM, null, self::A_NONE],
            'a' => [static::T_NUM, null, 0],
            'b' => [static::T_NUM, null, 0],
        ];
    }

    /**
     * Return a string description for this AlertLog.
     * @param boolean $name Whether to name the actor.
     * @return string A description.
     */
    public function getDescription($name=true) {
        $desc_parts = [];

        switch($this->obj['action']) {
            case static::A_CREATE:
                $desc_parts[] = 'Alert created';
                break;
            case static::A_NOTE:
                if($name) {
                    $desc_parts[] = Assignee::getName(Assignee::T_USER, $this->obj['user_id']);
                }
                $desc_parts[] = 'added a note';
                break;
            case static::A_ESCALATE:
                if($name) {
                    $desc_parts[] = Assignee::getName(Assignee::T_USER, $this->obj['user_id']);
                }
                $desc_parts[] = $this->obj['a'] ? 'escalated':'de-escalated';
                break;
            case static::A_ASSIGN:
                if($name) {
                    $desc_parts[] = Assignee::getName(Assignee::T_USER, $this->obj['user_id']);
                }
                $assign = $this->obj['a'] !== 0 || $this->obj['b'] !== 0;
                $desc_parts[] = $assign ? 'assigned':'unassigned';
                if($assign) {
                    $desc_parts[] = 'to';
                    $desc_parts[] = Assignee::getName($this->obj['a'], $this->obj['b']);
                }
                break;
            case static::A_SWITCH:
                if($name) {
                    $desc_parts[] = Assignee::getName(Assignee::T_USER, $this->obj['user_id']);
                }
                $desc_parts[] = 'marked';
                $desc_parts[] = Alert::$STATES[$this->obj['a']];
                if($this->obj['a'] == Alert::ST_RES) {
                    $desc_parts[] = '(' . Alert::$RESOLUTIONS[$this->obj['b']] . ')';
                }
                break;
        }

        return implode(' ', $desc_parts);
    }
}

/**
 * Class AlertLogFinder
 * Finder for AlertLogs.
 * @package FOO
 * @method static AlertLog getById(int $id, bool $archived=false)
 * @method static AlertLog[] getAll()
 * @method static AlertLog[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static AlertLog[] hydrateModels($objs)
 */
class AlertLogFinder extends ModelFinder {
    public static $MODEL = 'AlertLog';

    /**
     * Get all actions on Alerts from a Search with a given notification type.
     * @param int $date The current date.
     * @param int $type The notification type.
     * @param int $range The range of time to query.
     * @return Alert[] An array of Alerts.
     */
    public static function getRecent($date, $type, $range) {
        $MODEL = 'FOO\\' . static::$MODEL;
        $sql = sprintf('
            SELECT * FROM `%s` INNER JOIN (
                SELECT A.`log_id`, MAX(A.`create_date`) FROM `%s` as A INNER JOIN `%s` as B USING(`alert_id`) INNER JOIN `%s` as C USING(`search_id`)
                WHERE A.`site_id` = ? AND C.`notif_type` = ? AND A.`action` != ? AND A.`create_date` >= ? AND A.`create_date` < ? AND A.`archived` = 0 AND B.`archived` = 0 AND C.`archived` = 0
                GROUP BY A.`alert_id`, `log_id`
                HAVING MAX(A.`log_id`)
            ) AS `tbl` USING(`log_id`)
        ', $MODEL::$TABLE, $MODEL::$TABLE, Alert::$TABLE, Search::$TABLE);

        $objs = DB::query($sql, [SiteFinder::getCurrentId(), $type, $MODEL::A_CREATE, $date - $range, $date]);
        return static::hydrateModels($objs);
    }

    /**
     * Get a (approximate) count of Alert resoutions.
     * @param int $from The lower time threshold
     * @param int $to The upper time threshold
     * @param int $count The number of results to return.
     * @return int A count of resolved Alerts by each User.
     * @throws DBException
     */
    public static function getRecentResolveCounts($from, $to, $count) {
        list($sql, $vals) = static::generateQuery([
            'user_id', 'COUNT(*) as count'
        ], [
            'action' => AlertLog::A_SWITCH,
            'a' => Alert::ST_RES,
            'create_date' => [
                self::C_GTE => $from,
                self::C_LT => $to,
        ]], $count, null, [['count', self::O_DESC]], ['user_id']);

        return DB::query(implode(' ', $sql), $vals);
    }
}
