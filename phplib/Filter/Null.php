<?php

namespace FOO;

/**
 * Class Null_Filter
 * Noop.
 * @package FOO
 */
class Null_Filter extends Filter {
    public static $TYPE = 'null';

    public static $DESC = 'No-op';

    /**
     * Merely returns the input Alert.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        return [$alert];
    }
}
