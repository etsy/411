<?php

namespace ESQuery;

class Settings {
    // Local
    /** @var string Host name of the host to query. Passed through to the ES client library. */
    public $host = 'localhost:9200';
    /** @var int To date when querying. */
    public $to = null;
    /** @var int From date when querying. */
    public $from = null;
    /** @var int Max result size. */
    public $size = 100;
    /** @var bool Whether to allow leading wildcards in the query. */
    public $allow_leading_wildcard = false;
    /** @var string|null Base name of the index to query on. */
    public $index = null;
    /** @var string The date field to query on. */
    public $date_field = null;
    /** @var bool Whether the index is date based. */
    public $date_based = false;

    // Global
    /** @var string[] Fields to return. */
    public $fields = [];
    /** @var string[] Mapping of fields to rename. */
    public $map = [];
    /** @var bool Whether to flatten arrays. */
    public $flatten = true;
    /** @var array[] Fields to sort the results on. */
    public $sort = [];
    /** @var bool Whether to return count of results. */
    public $count = false;

    public function __construct() {
        $this->to = $this->from = time();
    }

    // Types
    const T_NULL = 0;
    const T_BOOL = 1;
    const T_INT = 2;
    const T_STR = 3;
    const T_ARR = 4;

    public static $KEYS = [
        'host' => self::T_STR,
        'to' => self::T_INT,
        'from' => self::T_INT,
        'size' => self::T_INT,
        'allow_leading_wildcard' => self::T_BOOL,
        'index' => [self::T_NULL, self::T_STR],
        'date_field' => self::T_STR,
        'date_based' => T_BOOL,

        'fields' => self::T_ARR,
        'map' => self::T_ARR,
        'flatten' => self::T_BOOL,
        'sort' => self::T_ARR,
        'count' => self::T_INT,
    ];

    /**
     * Load settings from an array.
     * @param array $arr Settings array.
     */
    public function load($arr) {
        foreach($arr as $key=>$value) {
            if(array_key_exists($key, self::$KEYS)) {
                if($this->valid(self::$KEYS[$key], $value)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Check type.
     * @param int|int[] $types
     * @param mixed $value
     * @return bool Valid
     */
    private function valid($types, $value) {
        $types = (array) $types;

        foreach($types as $type) {
            switch($type) {
            case self::T_NULL:
                if(is_null($value)) {
                    return true;
                }
                break;
            case self::T_INT:
                if(is_int($value)) {
                    return true;
                }
                break;
            case self::T_STR:
                if(is_string($value)) {
                    return true;
                }
                break;
            case self::T_ARR:
                if(is_array($value)) {
                    return true;
                }
                break;
            }
        }

        return false;
    }

    /**
     * Create a new object, copying over global settings.
     * @return Settings
     */
    public function copy() {
        $settings = new Settings();
        $settings->fields = $this->fields;
        $settings->map = $this->map;
        $settings->flatten = $this->flatten;
        $settings->sort = $this->sort;
        $settings->count = $this->count;
        return $settings;
    }
}
