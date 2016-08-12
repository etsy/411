<?php

namespace FOO;

/**
 * Class ReportTarget
 * A ReportTarget is an entry in a Report.
 * @package FOO
 */
class ReportTarget extends Model {
    public static $TABLE = 'report_targets';
    public static $PKEY = 'report_target_id';

    protected static function generateSchema() {
        return [
            'report_id' => [static::T_NUM, null, 0],
            'search_id' => [static::T_NUM, null, 0],
            'position' => [static::T_NUM, null, 0],
        ];
    }
}

/**
 * Class ReportTargetFinder
 * Finder for ReportTargets.
 * @package FOO
 * @method static ReportTarget getById(int $id, bool $archived=false)
 * @method static ReportTarget[] getAll()
 * @method static ReportTarget[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static ReportTarget[] hydrateModels($objs)
 */
class ReportTargetFinder extends ModelFinder {
    public static $MODEL = 'ReportTarget';

    /**
     * Get ReportTargets for a given Report.
     * @param int $id The Report id.
     * @return ReportTarget[] An array of ReportTargets.
     */
    public static function getByReport($id) {
        return self::getByQuery(['report_id' => $id]);
    }
}
