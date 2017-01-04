<?php

namespace FOO;

/**
 * Class Config
 * Static config values.
 * @package FOO
 */
class Config {
    /** @var array Config data. */
    private static $cfg = null;

    /**
     * Initialize config values.
     */
    public static function init($cfg) {
        self::$cfg = $cfg;
    }

    /**
     * Get config array. For testing.
     */
    public static function getData() {
        return self::$cfg;
    }

    /**
     * Get a config value.
     * @param string $key
     * @return mixed The config value.
     */
    public static function get($key) {
        return Util::get(self::$cfg, $key);
    }
}
