<?php

namespace FOO;

/**
 * Class User
 * Represents a user of 411. Can be added to a Group or assigned Alerts/Searches.
 * @package FOO
 */
class User extends Model {
    public static $TABLE = 'users';
    public static $PKEY = 'user_id';

    /** Invalid user. */
    const NONE = 0;

    protected static function generateSchema() {
        return [
            'name' => [static::T_STR, null, ''],
            'real_name' => [static::T_STR, null, ''],
            'password' => [static::T_STR, null, ''],
            'email' => [static::T_STR, null, ''],
            'admin' => [static::T_BOOL, null, false],
            'settings' => [static::T_OBJ, null, []],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);
        if(strlen(trim($data['name'])) == 0) {
            throw new ValidationException('Invalid name');
        }
        if(strlen($data['real_name']) == 0) {
            throw new ValidationException('Invalid real name');
        }
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email');
        }
        if(strlen($data['password']) == 0) {
            throw new ValidationException('Invalid password');
        }
    }

    protected function serialize(array $data) {
        $data['settings'] = json_encode((object)$data['settings']);
        $data['admin'] = (bool)$data['admin'];
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['settings'] = json_decode($data['settings'], true);
        $data['admin'] = (bool)$data['admin'];
        return parent::deserialize($data);
    }
}

/**
 * Class UserFinder
 * Finder for Users.
 * @package FOO
 * @method static User getById(int $id, bool $archived=false)
 * @method static User[] getAll()
 * @method static User[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static User[] hydrateModels($objs)
 */
class UserFinder extends ModelFinder {
    public static $MODEL = 'User';

    /**
     * Find a user object by name.
     * @param string $name The username.
     * @return User|null A user object or null.
     */
    public static function getByName($name) {
        $models = static::getByQuery(['name' => $name]);
        if(!count($models)) {
            return null;
        }
        return $models[0];
    }
}
