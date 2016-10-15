<?php

namespace FOO;

/**
 * Class Report
 * A Report can be assigned to Alerts or Searches. That Report will then receive notifications for that object.
 */
class Report extends Model {
    public static $TABLE = 'reports';
    public static $PKEY = 'report_id';

    // Types.
    /** All active Searches type. */
    const T_AA = 0;
    /** Selection type. */
    const T_SEL = 1;
    /** @var string[] Mapping of states to a user-friendly string. */
    public static $TYPES = [
        self::T_AA => 'All Active',
        self::T_SEL => 'Selection'
    ];

    protected static function generateSchema() {
        return [
            'type' => [static::T_ENUM, static::$TYPES, self::T_AA],
            'name' => [static::T_STR, null, ''],
            'description' => [static::T_STR, null, ''],
            'frequency' => [static::T_NUM, null, 1],
            'range' => [static::T_NUM, null, 1],
            'assignee_type' => [static::T_ENUM, Assignee::$TYPES, Assignee::T_USER],
            'assignee' => [static::T_NUM, null, User::NONE],
            'enabled' => [static::T_BOOL, null, true],
            'start_date' => [static::T_NUM, null, 0],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(strlen(trim($data['name'])) == 0) {
            throw new ValidationException('Invalid name');
        }
        if($data['frequency'] < 1) {
            throw new ValidationException('Invalid frequency');
        }
        if($data['range'] < 1) {
            throw new ValidationException('Invalid range');
        }
    }

    /**
     * Return the set of Searches for this Report.
     * @return Search[] An array of Search objects.
     */
    public function getSearches() {
        $ret = [];
        switch($this->obj['type']) {
            case self::T_AA:
                $ret = SearchFinder::getByQuery(['enabled' => 1]);
                break;
            case self::T_SEL:
                $reporttargets = ReportTargetFinder::getByReport($this->obj[static::$PKEY]);

                foreach($reporttargets as $reporttarget) {
                    $search = SearchFinder::getById($reporttarget['search_id']);
                    if(!is_null($search)) {
                        $ret[] = $search;
                    }
                }
                break;
        }
        return $ret;
    }

    protected function serialize(array $data) {
        $data['enabled'] = (bool)$data['enabled'];
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['enabled'] = (bool)$data['enabled'];
        return parent::deserialize($data);
    }

    /**
     * Whether this Report should run at the current date.
     * @param int $date The current date.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     * @return bool Whether the Report should run.
     */
    public function shouldRun($date, $backfill=false) {
        $due = false;
        $job = JobFinder::getLastByQuery(['type' => Report_Job::$TYPE, 'target_id' => $this->obj[static::$PKEY]]);

        $last_scheduled_date = is_null($job) ? $this->obj['start_date']:$job['target_date'];
        $delta = $date - $last_scheduled_date + 5;

        if($backfill) {
            $due = ($date / 60) % ($this->obj['frequency'] * 60 * 24) == 0;
        } else {
            $due = $delta >= $this->obj['frequency'] * 60 * 60 * 24;
        }

        return $due;
    }
}

/**
 * Class ReportFinder
 * Finder for Reports.
 * @package FOO
 * @method static Report getById(int $id, bool $archived=false)
 * @method static Report[] getAll()
 * @method static Report[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Report[] hydrateModels($objs)
 */
class ReportFinder extends ModelFinder {
    public static $MODEL = 'Report';
}
