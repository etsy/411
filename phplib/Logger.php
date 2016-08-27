<?php

namespace FOO;

/**
 * Class Logger
 * Provides logging functionality.
 * @package FOO
 */
class Logger {

    /**
     * Initialize the class.
     */
    public static function init() {}

    /**
     * Log a debug line.
     * @param string $msg The message.
     * @param array $arr The data.
     * @param string $ns The namespace.
     */
    public static function dbg($msg, $arr=[], $ns='NONE') {
        if(php_sapi_name() != 'cli' && Util::isDevelopment()) {
            self::log('dbg', $msg, $arr, $ns);
        }
    }

    /**
     * Log an info line.
     * @param string $msg The message.
     * @param array $arr The data.
     * @param string $ns The namespace.
     */
    public static function info($msg, $arr=[], $ns='NONE') {
        if(php_sapi_name() != 'cli') {
            self::log('info', $msg, $arr, $ns);
        }
    }

    /**
     * Log a warning line.
     * @param string $msg The message.
     * @param array $arr The data.
     * @param string $ns The namespace.
     */
    public static function warn($msg, $arr=[], $ns='NONE') {
        self::log('warn', $msg, $arr, $ns);
    }

    /**
     * Log an error line.
     * @param string $msg The message.
     * @param array $arr The data.
     * @param string $ns The namespace.
     */
    public static function err($msg, $arr=[], $ns='NONE') {
        self::log('err', $msg, $arr, $ns);
    }

    /**
     * Log an exception.
     * @param \Exception $exception The exception.
     * @param string $ns The namespace.
     */
    public static function except($exception, $ns='NONE') {
        self::backtrace(
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace(),
            0, $ns
        );
    }

    /**
     * Log a backtrace.
     * @param string $label Type of the error.
     * @param string $desc Description of the error.
     * @param string $file File name.
     * @param int $line Line number.
     * @param array|null $bt Backtrace array.
     * @param int $skip The number of frames to skip.
     * @param string $ns The namespace.
     */
    public static function backtrace($label, $desc, $file, $line, $bt=null, $skip=0, $ns='NONE') {
        if(is_null($bt)) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        self::log('except', sprintf('%s: "%s" at [%s:%d]',
            $label, $desc, $file, $line
        ), self::bt_fmt($bt, $skip), $ns);
    }

    /**
     * Format and return a backtrace string.
     * @param array $bt Backtrace array.
     * @param int $skip The number of frames to skip.
     * @return string Backtrace string.
     */
    private static function bt_fmt($bt, $skip=0) {
        $bt_arr = [];
        for($i = $skip; $i < count($bt); ++$i) {
            $f = $bt[$i];
            $func = Util::get($f, 'class', '') . Util::get($f, 'type', '') . Util::get($f, 'function', '');
            $bt_arr[] = sprintf('%s() called at [%s:%d]',
                $func, Util::get($f, 'file', ''), Util::get($f, 'line', 0)
            );
        }

        return $bt_arr;
    }

    /**
     * Log a line.
     * @param string $type The log type.
     * @param string $msg The message.
     * @param array $arr Additional data.
     * @param string $ns The namespace.
     */
    private static function log($type, $msg, $arr, $ns) {
        error_log(sprintf("%s [%s] %s %s", $type, $ns, $msg, self::serialize($arr)));
    }

    /**
     * Serializes data to a string for logging.
     * @param mixed $arr Input data.
     * @return mixed Serialized data.
     */
    public static function serialize($arr) {
        $s_arr = [];

        if(is_null($arr)) {
            return 'null';
        }

        if(is_string($arr)) {
            return $arr;
        }

        if(is_bool($arr)) {
            return (int)$arr;
        }

        foreach($arr as $k=>$v) {
            if(is_array($v)) {
                $s_arr[] = sprintf("%s:[%s]", $k, self::serialize($v));
            } else if(is_object($v)) {
                $s_arr[] = sprintf("%s:obj(%s)", $k, gettype($v));
            } else {
                $s_arr[] = sprintf("%s:[%s]", $k, $v);
            }
        }
        return implode(' ', $s_arr);
    }
}
