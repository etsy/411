<?php

namespace FOO;

/**
 * Class Target
 * A Target class takes Alerts objects and executes some action with it.
 * The interface for this class is identical to the Filter class.
 * @package FOO
 */
abstract class Target extends Element {
    public static $TYPES = ['Null_Target', 'WebHook_Target', 'Jira_Target', 'Slack_Target'];
    public static $TABLE = 'search_targets';
    public static $PKEY = 'target_id';

    protected static function generateSchema() {
        return [
            'search_id' => [static::T_NUM, null, 0],
            'lifetime' => [static::T_NUM, null, 0],
            'description' => [static::T_STR, null, ''],
            'data' => [static::T_OBJ, null, []],
        ];
    }

    /**
     * Creates a new Target of the appropriate type.
     * @param string $type The type of the Target.
     * @param array $data The attributes for the Target.
     * @return Target The new Target.
     */
    public static function newTarget($type, $data=null) {
        return self::newElement($type, $data);
    }

    /**
     * Process an Alert object.
     * @param Alert $alert An Alert object
     * @param int $date The current date.
     */

    /**
     * Finish processing any alerts.
     * @param int $date The current date.
     */
    public function finalize($date) {}
}

/**
 * Class TargetFinder
 * Finder for Targets.
 * @package FOO
 * @method static Target getById(int $id, bool $archived=false)
 * @method static Target[] getAll()
 * @method static Target[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Target[] hydrateModels($objs)
 */
class TargetFinder extends ElementFinder {
    public static $MODEL = 'Target';
}

/**
 * Exception thrown when there is an error executing the Target.
 */
class TargetException extends \Exception {}
