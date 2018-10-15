<?php

namespace FOO;

/**
 * SLog class
 * Represents a log entry.
 * @package FOO
 */
class SLog extends Model {
    public static $TABLE = 'slogs';
    public static $PKEY = 'slog_id';

    // Generic actions
    /** Noop action. */
    const A_NONE = 0;
    /** Create action. */
    const A_CREATE = 1;
    /** Update action. */
    const A_UPDATE = 2;
    /** Delete action. */
    const A_DELETE = 3;

    // Alert actions
    /** Escalate alert action. */
    const AA_ESCALATE = 4;
    /** Assignment alert action. */
    const AA_ASSIGN = 5;
    /** State alert action. */
    const AA_SWITCH = 6;
    /** Note alert action. */
    const AA_NOTE = 7;

    // Search actions
    /** Test search action. */
    const AS_TEST = 4;
    /** Execute search action. */
    const AS_EXECUTE = 5;

    // Types
    /** Alert action type. */
    const T_ALERT = 1;
    /** Search action type. */
    const T_SEARCH = 2;
    /** Filter action type. */
    const T_FILTER = 3;
    /** Target action type. */
    const T_TARGET = 4;
    /** Group action type. */
    const T_GROUP = 5;
    /** GroupTarget action type. */
    const T_GROUPTARGET = 6;
    /** User action type. */
    const T_USER = 7;
    /** List action type. */
    const T_SLIST = 10;

    protected static function generateSchema() {
        return [
            'type' => [static::T_NUM, null, 0],
            'action' => [static::T_NUM, null, self::A_NONE],
            'target' => [static::T_NUM, null, 0],
            'actor' => [static::T_NUM, null, User::NONE],
            'a' => [static::T_NUM, null, 0],
            'b' => [static::T_NUM, null, 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     * @param int $type The type of object being acted on.
     * @param int $action The action taken.
     * @param int $target The target id.
     * @param int $actor The actor id.
     * @param int $a Additional data.
     * @param int $b Additional data.
     */
    public static function entry($type, $action, $target=0, $actor=0, $a=0, $b=0) {
        $slog = new static();
        $slog['type'] = $type;
        $slog['action'] = $action;
        $slog['target'] = $target;
        $slog['actor'] = $actor;
        $slog['a'] = $a;
        $slog['b'] = $b;
        $slog->store();
    }
}

/**
 * Class LogFinder
 * @package FOO
 * @method static SLog getById(int $id, bool $archived=false)
 * @method static SLog[] getAll()
 */
class LogFinder extends ModelFinder {
    public static $MODEL = 'SLog';
}
