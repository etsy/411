<?php

namespace FOO;

/**
 * Class Util
 * Contains miscellaneous functions.
 * @package FOO
 */
class Util {
    /**
     * Return the value of a key or a default value.
     * @param array|\ArrayAccess $arr The array.
     * @param string|int $key The key.
     * @param mixed $default The default value to return.
     * @return mixed|null The value of that key.
     */
    public static function get($arr, $key, $default=null) {
        return self::exists($arr, $key) ? $arr[$key]:$default;
    }

    /**
     * Determines whether an array contains a certain key.
     * @param array|\ArrayAccess $arr The array.
     * @param string|int $key The key.
     * @return bool true if the key exists and false otherwise.
     */
    public static function exists($arr, $key) {
        // If it's an object, index directly because array_key_exists doesn't
        // work with the ArrayAccess interface. Otherwise, check if it implements
        // ArrayAccess and fall back to array_key_exists.
        if(is_object($arr)) {
            return $arr->offsetExists($key);
        }
        if(is_array($arr)) {
            return array_key_exists($key, $arr);
        }

        return false;
    }

    /**
     * Escapes data for displaying in a browser.
     * @param string $data Raw data to escape.
     * @return string The escaped data.
     */
    public static function escape($data) {
        return htmlentities($data, ENT_QUOTES, 'utf-8');
    }

    /**
     * Sends a 302 redirect to the browser.
     * @param string $url The target.
     */
    public static function redirect($url, $exit=true) {
        header("Location: $url");
        exit(0);
    }

    /**
     * Returns whether we're running in a development environment.
     * @return bool True if in a dev environment.
     */
    public static function isDevelopment() {
        return false;
    }

    /**
     * Returns whether we're running in a test environment.
     * @return bool True if in a test environment.
     */
    public static function isTesting() {
        global $TESTING;
        return (bool) $TESTING;
    }

    /**
     * Prompt for input.
     * @param string $str The string to display.
     * @return string An input string.
     */
    public static function prompt($str) {
        $ret = '';
        while(true) {
            printf("%s: ", $str);
            $ret = fgets(STDIN);
            if($ret === false) {
                exit(1);
            }
            $ret = trim($ret);
            if(strlen($ret)) {
                break;
            }
        }

        return $ret;
    }

    /**
     * Exec a command.
     * @param string $path The executable to run.
     * @param string[] $args ARGV
     * @param array $env ENVP
     * @return int|null The status code on success or null on error.
     */
    public static function exec($path, $args, $env=[]) {
        $pid = pcntl_fork();
        if($pid == -1) {
            return null;
        }

        if($pid == 0) {
            pcntl_exec($path, $args, $env);
            exit(1);
        }

        if(pcntl_waitpid($pid, $status) == -1) {
            return null;
        }

        if(!pcntl_wifexited($status)) {
            return null;
        }

        return pcntl_wexitstatus($status);
    }

    /**
     * Get the name of the 411 instance
     * @return string The name.
     */
    public static function getSiteName() {
        $name = '411';
        $site = SiteFinder::getCurrent();
        if($site) {
            $name = $site['name'];
        }
        return $name;
    }

    /**
     * Get the displayed host.
     * @return string The hostname.
     */
    public static function getHost() {
        $host = 'fouroneone';
        $site = SiteFinder::getCurrent();
        if($site) {
            $host = $site['host'];
        }
        return $host;
    }
}
