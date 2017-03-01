<?php

namespace FOO;

/**
 * Class Cleanup_Job
 * Represents a scheduled job to close stale Alerts.
 * @package FOO
 */
class Cleanup_Job extends Job {
    public static $TYPE = 'cleanup';

    /**
     * Optimize 411 by doing some cleanup.
     * @return array null, array of errors and whether failures are ignorable.
     */
    public function run() {
        JobFinder::optimize($this->obj['target_date']);

        return [null, [], false];
    }
}
