<?php

namespace FOO;

/**
 * Class Filter
 * An Filter takes Alert objects, transforms them in some manner (possibly creating additional Alerts in the process)
 * and then outputs them.
 * @package FOO
 */
abstract class Filter extends Element {
    public static $TYPES = ['Null_Filter', 'Dedupe_Filter', 'Throttle_Filter', 'Hash_Filter', 'Regex_Filter', 'Script_Filter', 'Enricher_Filter', 'Expression_Filter', 'MapKey_Filter', 'MapValue_Filter'];
    public static $TABLE = 'search_filters';
    public static $PKEY = 'filter_id';

    protected static function generateSchema() {
        return [
            'search_id' => [static::T_NUM, null, 0],
            'position' => [static::T_NUM, null, 0],
            'lifetime' => [static::T_NUM, null, 0],
            'description' => [static::T_STR, null, ''],
            'data' => [static::T_OBJ, null, []],
        ];
    }


    /**
     * Creates a new Filter of the appropriate type.
     * @param string $type The type of the Filter.
     * @param array $data The attributes for the Filter.
     * @return Filter The new Filter.
     */
    public static function newFilter($type, $data=null) {
        return self::newElement($type, $data);
    }

    /**
     * Process an Alert object, applying some sort of transformation to the code. May output the original object,
     * additional objects, or none at all.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] An array of Alert objects.
     */

    /**
     * Finish processing these alerts and return any remaining Alert objects.
     * @param int $date The current date.
     * @return Alert[] An array of Alert objects.
     */
    public function finalize($date) {
          return [];
    }
};

/**
 * Class FilterFinder
 * Finder for Filters.
 * @package FOO
 * @method static Filter getById(int $id, bool $archived=false)
 * @method static Filter[] getAll()
 * @method static Filter[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Filter[] hydrateModels($objs)
 */
class FilterFinder extends ElementFinder {
    public static $MODEL = 'Filter';
}

/**
 * Exception thrown when there is an error executing the Filter.
 */
class FilterException extends \Exception {}
