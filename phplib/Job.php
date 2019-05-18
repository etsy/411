<?php

namespace FOO;

/**
 * Class Job
 * Represents a scheduled execution of a task.
 * @package FOO
 */
abstract class Job extends TypeModel {
    const LOG_NAMESPACE = 'JOB';
    const MAX_TRIES = 5;

    public static $TYPES = [
        Search_Job::class,
        Rollup_Job::class,
        Summary_Job::class,
        Autoclose_Job::class,
        Sync_Job::class,
        Cleanup_Job::class,
        Delete_Job::class,
    ];
    public static $TABLE = 'jobs';
    public static $PKEY = 'job_id';

    // States.
    /** Pending job state. */
    const ST_PEND = 0;
    /** Successful job state. */
    const ST_SUCC = 1;
    /** Failed job state. */
    const ST_FAIL = 2;
    /** Canceled job state. */
    const ST_CANC = 3;
    /** Running job state. */
    const ST_RUN = 4;
    /** @var string[] Mapping of states to a user-friendly string. */
    public static $STATES = [
        self::ST_PEND => 'Pending',
        self::ST_SUCC => 'Success',
        self::ST_FAIL => 'Failure',
        self::ST_CANC => 'Cancelled',
        self::ST_RUN => 'Running'
    ];

    protected static function generateSchema() {
        return [
            'target_id' => [static::T_NUM, null, 0],
            'state' => [static::T_ENUM, static::$STATES, self::ST_PEND],
            'completion' => [static::T_NUM, null, 0],
            'tries' => [static::T_NUM, null, 0],
            'target_date' => [static::T_NUM, null, 0],
            'last_execution_date' => [static::T_NUM, null, 0]
        ];
    }

    /**
     * Run the job.
     * @return array Generated data and an array of errors.
     * @throws JobCancelException
     * @throws JobFailException
     */
    abstract public function run();

    /**
     * Called if the job succeeds for any extra processing.
     */
    public function onSuccess() {}

    /**
     * Called if the job fails so cleanup can be done.
     * @param string[] $errors An array of error strings.
     */
    public function onFail(array $errors) {}

    /**
     * Called once the job has terminated, regardless of whether if succeeded or failed.
     */
    public function onFinish() {}

    /**
     * Whether this job should run.
     * @param int $date The current timestamp.
     * @return bool Whether to run.
     */
    public function shouldRun($date) {
        return true;
    }

    /**
     * Whether or not this job should be rescheduled if there was a problem.
     * Only called if no exception was thrown!
     * @param int $date The current timestamp.
     * @return bool Whether to reschedule.
     */
    public function shouldRetry($date) {
        return true;
    }

    /**
     * Change the completion of this job and persist to the DB.
     * @param int $percent How much progress this job has made.
     */
    public function setCompletion($percent) {
        $this->obj['completion'] = $percent;
        if(!$this->isNew()) {
            $this->update();
        }
    }

    /**
     * Get debugging data.
     * @return array[] An array of debug data.
     */
    public function getDebugData() {
        return $this->toArray(['type', 'target_id', 'target_date', 'tries']);
    }
}

/**
 * Class JobFinder
 * Finder for Jobs.
 * @package FOO
 * @method static Job getById(int $id, bool $archived=false)
 * @method static Job[] getAll()
 * @method static Job[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Job[] hydrateModels($objs)
 */
class JobFinder extends TypeModelFinder {
    public static $MODEL = 'Job';

    /**
     * Fail or cancel any Jobs that haven't made progress in the last 20 minutes.
     * @param int $date The current date.
     * @throws DBException
     */
    public static function fail($date) {
        $threshold = $date - (20 * 60);

        // Cancel any jobs that have failed more than MAX_TRIES times.
        $sql = sprintf('
            UPDATE `%s` SET `state` = ?, `update_date` = ?
            WHERE `site_id` = ? AND `archived` = 0 AND `state` IN %s AND `update_date` < ? AND `tries` > ?',
        Job::$TABLE, DB::inPlaceholder(2));
        DB::query($sql, [Job::ST_CANC, $date, SiteFinder::getCurrentId(), Job::ST_RUN, Job::ST_FAIL, $threshold, Job::MAX_TRIES]);

        // Fail any jobs that have failed less than MAX_TRIES times.
        $sql = sprintf('
            UPDATE `%s` SET `state` = ?, `update_date` = ?
            WHERE `site_id` = ? AND `archived` = 0 AND `state` IN %s AND `update_date` < ? AND `tries` <= ?',
        Job::$TABLE, DB::inPlaceholder(2));
        DB::query($sql, [Job::ST_PEND, $date, SiteFinder::getCurrentId(), Job::ST_RUN, Job::ST_FAIL, $threshold, Job::MAX_TRIES]);
    }

    /**
     * Get the ids of all pending Jobs.
     * @return int[] A list of pending ids.
     */
    public static function getPendingIds() {
        $sql = sprintf(
            "SELECT `%s` FROM `%s` WHERE `site_id` = ? AND `archived` = 0 AND `state` = ?",
            Job::$PKEY, Job::$TABLE
        );
        $ret = DB::query($sql, [SiteFinder::getCurrentId(), Job::ST_PEND], DB::COL);

        return $ret;
    }

    /**
     * Attempt to get a lock on a Job.
     * @param int $id The Job id.
     * @param int $date The current date.
     * @return Job|null A Job if successful or null.
     */
    public static function getAndLock($id, $date) {
        $sql = sprintf(
            "UPDATE `%s` SET `state` = ?, `update_date` = ?, `tries` = `tries` + 1, `completion` = 0, `last_execution_date` = ? WHERE `site_id` = ? AND `archived` = 0 AND `state` = ? AND `%s` = ?",
            Job::$TABLE, Job::$PKEY
        );
        $ret = DB::query($sql, [Job::ST_RUN, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], SiteFinder::getCurrentId(), Job::ST_PEND, $id], DB::CNT);

        return $ret ? static::getById($id):null;
    }

    /**
     * Get counts of Jobs and what state they're in.
     * @return array The counts of Jobs.
     */
    public static function getCounts() {
        $sql = sprintf(
            'SELECT `state`, COUNT(*) as `count` FROM `%s` WHERE `site_id` = ? AND `archived` = 0 GROUP BY `state`',
            Job::$TABLE
        );

        $ret = array_fill(0, count(Job::$STATES), 0);
        foreach(DB::query($sql, [SiteFinder::getCurrentId()]) as $row) {
            $ret[$row['state']] = (int)$row['count'];
        }
        return $ret;
    }

    /**
     * Get the Job with the most recent target_date given a query.
     * @param array $query The query parameters.
     * @return Job|null A Job or null.
     */
    public static function getLastByQuery($query) {
        $ret = static::getByQuery($query, 1, null, [['target_date', self::O_DESC]]);
        return count($ret) ? $ret[0]:null;
    }

    /**
     * Archive old jobs. (Older than 30 days)
     * @param int $date The current date.
     */
    public static function optimize($date) {
        $sql = sprintf('
            DELETE FROM `%s` WHERE `site_id` = ? AND `archived` = 0 AND `state` IN (?, ?) AND `update_date` < ?',
        Job::$TABLE);
        DB::query($sql, [SiteFinder::getCurrentId(), Job::ST_SUCC, Job::ST_CANC, $date - (30 * 24 * 60 * 60)]);
    }
}

/**
 * Class JobFatalException
 * Thrown where there is an exception running the Job.
 * This type of exception cancels the job.
 * @package FOO
 */
class JobCancelException extends \Exception {}

/**
 * Class JobTempException
 * Thrown where there is an exception running the Job.
 * This type of exception reschedules the job for later.
 * @package FOO
 */
class JobFailException extends \Exception {}
