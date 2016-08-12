<?php

namespace FOO;

/**
 * Class Group
 * A Group can be assigned to Alerts or Searches. That Group will then receive notifications for that object.
 * @package FOO
 */
class Group extends Model {
    public static $TABLE = 'groups';
    public static $PKEY = 'group_id';

    // Types.
    /** All assignee type. */
    const T_ALL = 0;
    /** Assignee rotation type. */
    const T_ROT = 1;
    /** @var Mapping of types to a user-friendly string. */
    public static $TYPES = [
        self::T_ALL => 'All',
        self::T_ROT => 'Rotation'
    ];

    protected static function generateSchema() {
        return [
            'type' => [static::T_ENUM, static::$TYPES, self::T_ALL],
            'state' => [static::T_NUM, null, 0],
            'name' => [static::T_STR, null, '']
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(strlen(trim($data['name'])) == 0) {
            throw new ValidationException('Invalid name');
        }
        if($data['state'] < 0) {
            throw new ValidationException('Invalid state');
        }
    }

    /**
     * Pull out emails from this group.
     * @param bool $update Determines whether a rotation Group will be updated after the call.
     * @param bool $all Determines whether a rotation Group will return all emails.
     * @return string[] An array of emails.
     */
    public function getEmails($update, $all) {
        $targets = GroupTargetFinder::getByGroup($this->obj[static::$PKEY]);
        $ret = array_map(function($x) {
            return $x->getEmail();
        }, $targets);

        $count = count($targets);
        // If there are no emails, there's no point in processing further.
        if($count) {
            switch($this->obj['type']) {
                case self::T_ROT:
                    if(!$all) {
                        $ret = [$ret[$this->obj['state'] % $count]];
                    }
                    break;
            }

            if($update) {
                $this->obj['state'] = ($this->obj['state'] + 1) % $count;
            }
        }

        return $ret;
    }
}

/**
 * Class GroupFinder
 * Finder for Groups.
 * @package FOO
 * @method static Group getById(int $id, bool $archived=false)
 * @method static Group[] getAll()
 * @method static Group[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Group[] hydrateModels($objs)
 */
class GroupFinder extends ModelFinder {
    public static $MODEL = 'Group';
}
