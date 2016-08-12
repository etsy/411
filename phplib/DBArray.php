<?php

namespace FOO;

/**
 * Class DBArray
 * Array values backed by the DB.
 * @package FOO
 */
class DBArray implements \ArrayAccess {
    /** @var string Database table name for this class. */
    public static $TABLE = '';
    /** @var int Site ID of this array. */
    protected $site_id = 0;

    /**
     * Constructor
     */
    public function __construct() {
        $this->site_id = SiteFinder::getCurrentId();
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @return mixed
     * @throws DBException
     */
    public function offsetExists($key) {
        return DB::query(sprintf('SELECT COUNT(*) FROM `%s` WHERE `site_id` = ? AND `key` = ?', static::$TABLE), [$this->site_id, $key], DB::VAL);
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @return int|mixed
     * @throws DBException
     */
    public function offsetGet($key) {
        $value = DB::query(sprintf('SELECT `value` FROM `%s` WHERE `site_id` = ? AND `key` = ?', static::$TABLE), [$this->site_id, $key], DB::VAL);
        if(ctype_digit($value)) {
            $value = (int) $value;
        }
        return $value;
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     * @throws DBException
     */
    public function offsetSet($key, $value) {
        return DB::query(sprintf('REPLACE INTO `%s` VALUES (?, ?, ?)', static::$TABLE), [$this->site_id, $key, $value], DB::CNT);
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @throws DBException
     */
    public function offsetUnset($key) {
        DB::query(sprintf('DELETE FROM `%s` WHERE `site_id` = ? AND `key` = ?', static::$TABLE), [$this->site_id, $key]);
    }
}
