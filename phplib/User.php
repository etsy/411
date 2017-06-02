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
    /** Api key length. */
    const API_KEY_LEN = 30;
    /** Default password length. */
    const PASS_LEN = 16;

    protected static function generateSchema() {
        return [
            'name' => [static::T_STR, null, ''],
            'real_name' => [static::T_STR, null, ''],
            'password' => [static::T_STR, null, ''],
            'email' => [static::T_STR, null, ''],
            'admin' => [static::T_BOOL, null, false],
            'timezone' => [static::T_STR, null, ''],
            'settings' => [static::T_OBJ, null, []],
            'api_key' => [static::T_STR, null, ''],
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
        if($data['timezone'] != '' && !in_array($data['timezone'], timezone_identifiers_list())) {
            throw new ValidationException('Invalid timezone');
        }
    }

    protected function serialize(array $data) {
        $data['settings'] = json_encode((object)$data['settings']);
        $data['admin'] = (bool)$data['admin'];
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['settings'] = (array)json_decode($data['settings'], true);
        $data['admin'] = (bool)$data['admin'];
        return parent::deserialize($data);
    }

    /**
     * Change the password for this user.
     * @param string $password The new password.
     */
    public function setPassword($password) {
        $this->obj['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Generate and set a random password.
     * @return string The new password.
     */
    public function randomizePassword() {
        $password = Random::base64_bytes(self::PASS_LEN);
        $this->setPassword($password);
        return $password;
    }

    /**
     * Generate and set a random API key.
     * @return string The new API key.
     */
    public function randomizeAPIKey() {
        $this->obj['api_key'] = Random::base64_bytes(User::API_KEY_LEN);
        return $this->obj['api_key'];
    }

    /**
     * Get timezone for this user, if set or default to sitewide timezone otherwise.
     * @return string Timezone string.
     */
    public function getTimezone() {
        $timezone = Util::validateTimezone($this->obj['timezone'], null);
        if(is_null($timezone)) {
            $config = new DBConfig;
            $timezone = Util::validateTimezone($config['timezone']);
        }
        return $timezone;
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

    /**
     * Find a user object by api key.
     * @param string $api_key The api key.
     * @return User|null A user object or null.
     */
    public static function getByAPIKey($api_key) {
        $models = static::getByQuery(['api_key' => $api_key]);
        if(!count($models)) {
            return null;
        }
        return $models[0];
    }
}
