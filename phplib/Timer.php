<?php

namespace FOO;

/**
 * Class Timer
 * Benchmarking functionality.
 * @package FOO
 */
class Timer {
    /** @var int Start time. */
    private $start;
    /** @var int End time. */
    private $end;

    /**
     * Start the timer.
     */
    public function start() {
        $this->reset();
    }

    /**
     * Reset the timer.
     */
    public function reset() {
        $this->end = $this->start = microtime(true);
    }

    /**
     * Stop the timer.
     */
    public function stop() {
        $this->end = microtime(true);
    }

    /**
     * Retrieves the amount of time that has elapsed.
     * @return float The interval.
     */
    public function taken() {
        return $this->end - $this->start;
    }
}
