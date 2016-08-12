<?php

namespace FOO;

/**
 * Class Null_Target
 * Noop.
 * @package FOO
 */
class Null_Target extends Target {
    public static $TYPE = 'null';

    public static $DESC = 'No-op';

    /**
     * Does nothing.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {}
}
