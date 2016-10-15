<?php

namespace FOO;

/**
 * GroupTarget Class
 * A GroupTarget is an entry in a group.
 * @package FOO
 */
class GroupTarget extends Model {
    public static $TABLE = 'group_targets';
    public static $PKEY = 'group_target_id';

    // Types.
    /** User type. */
    const T_USER = 0;
    /** Email type. */
    const T_EMAIL = 1;
    /** @var string[] Mapping of states to a user-friendly string. */
    public static $TYPES = [
        self::T_USER => 'User',
        self::T_EMAIL => 'Email'
    ];

    protected static function generateSchema() {
        return [
            'group_id' => [static::T_NUM, null, 0],
            'type' => [static::T_ENUM, static::$TYPES, self::T_USER],
            'user_id' => [static::T_NUM, null, User::NONE],
            'data' => [static::T_STR, null, ''],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if($this->obj['user_id'] == User::NONE && strlen($this->obj['data']) == 0) {
            throw new ValidationException('Invalid user_id and data');
        }
    }

    /**
     * Retrieve the email address from this GroupTarget. If this is a user target, grab the user and pull their email.
     * Else, just use the field itself.
     * @return string A email address.
     */
    public function getEmail() {
        $ret = '';
        switch($this->obj['type']) {
            case self::T_USER:
                $user = UserFinder::getById($this->obj['user_id']);
                if(!is_null($user)) {
                    $ret = $user['email'];
                }
                break;
            case self::T_EMAIL:
                $ret = $this->obj['data'];
                break;
        }
        return $ret;
    }
}

/**
 * Class GroupTargetFinder
 * Finder for GroupTargets.
 * @package FOO
 * @method static GroupTarget getById(int $id, bool $archived=false)
 * @method static GroupTarget[] getAll()
 * @method static GroupTarget[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static GroupTarget[] hydrateModels($objs)
 */
class GroupTargetFinder extends ModelFinder {
    public static $MODEL = 'GroupTarget';

    /**
     * Find all GroupTargets in a Group.
     * @param int $id The Group id.
     * @return GroupTarget[] An array of GroupTargets.
     */
    public static function getByGroup($id) {
        return self::getByQuery(['group_id' => $id]);
    }
}
