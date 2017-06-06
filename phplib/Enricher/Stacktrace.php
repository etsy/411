<?php

namespace FOO;

/**
 * Stacktrace Enricher
 * Formats stacktraces.
 * @package FOO
 */
class Stacktrace_Enricher extends Enricher {
    public static $TYPE = 'stacktrace';

    public static function process($data) {
        return str_replace(['\\\\n', '\\n'], ['\n', '\n'], $data);
    }

    public static function processHTML($data) {
        return '<pre>' . htmlentities(self::process($data), ENT_QUOTES) . '</pre>';
    }
}
