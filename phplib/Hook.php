<?php

namespace FOO;

/**
 * Class Hook
 * Provides functionality to hook into 411 code.
 * @package FOO
 */
class Hook {
    /** @var callable[] Hook map. */
    private static $map = [];

    /**
     * Call any hooks registered at this site.
     * @param string $hook Name of the hook.
     * @param mixed[] $args Arguments.
     * @return mixed[] Arguments.
     */
    public static function call($hook, $args=[]) {
        if(!array_key_exists($hook, self::$map)) {
            return $args;
        }

        foreach(self::$map[$hook] as $func) {
            $args = $func($args);
        }

        return $args;
    }

    /**
     * Register a hook. The hooking function should accept an array of
     * values. It should make any modifications as necessary and return
     * the array.
     * @param $hook Name of the hook.
     * @param callable $func A function.
     */
    public static function register($hook, $func) {
        if(!array_key_exists($hook, self::$map)) {
            self::$map[$hook] = [];
        }
        self::$map[$hook][] = $func;
    }
}
