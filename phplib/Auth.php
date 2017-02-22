<?php

namespace FOO;

/**
 * Class Auth
 * Handles authentication related functionality.
 * @package FOO
 */
class Auth {
    const T_NULL = 0;
    const T_COOKIE = 1;
    const T_API = 2;
    const T_PROXY = 3;

    private static $user = null;
    private static $auth_type = self::T_NULL;

    public static function init() {
        $user = null;
        $auth_config = Config::get('auth');
        $api_auth = Util::get($auth_config['api'], 'enabled', false);
        $proxy_auth = Util::get($auth_config['proxy'], 'enabled', false);
        $proxy_auth_header = sprintf('HTTP_%s', strtoupper(Util::get($auth_config['proxy'], 'header', '')));

        if($api_auth && array_key_exists('HTTP_X_API_KEY', $_SERVER)) {
            self::$auth_type = self::T_API;
            $user = self::getAPIUser($_SERVER['HTTP_X_API_KEY']);
        } else if($proxy_auth && array_key_exists($proxy_auth_header, $_SERVER)) {
            self::$auth_type = self::T_PROXY;
            $user = self::getProxyUser($_SERVER[$proxy_auth_header]);
        } else {
            self::$auth_type = self::T_COOKIE;
            $user = self::getCookieUser(Cookie::get('id'));
        }

        list($user) = Hook::call('auth.init', [$user]);
        self::$user = $user;
    }

    private function getAPIUser($api_key) {
        $user = null;
        if(strlen($api_key) >= User::API_KEY_LEN) {
            $user = UserFinder::getByAPIKey($api_key);
        }

        return $user;
    }

    private function getProxyUser($username) {
        $auth_config = Config::get('auth');
        $auto_create = Util::get($auth_config['proxy'], 'auto_create', false);
        $domain = Util::get($auth_config['proxy'], 'domain', '411');

        $user = UserFinder::getByName($username);
        if (is_null($user) && $auto_create) {
            $user = self::createUser($username, $domain);
        }

        return $user;
    }

    /**
     * Create a new user.
     * @param string $username The username.
     * @param string $domain The domain.
     * @return User The new user object.
     */
    private static function createUser($username, $domain) {
      $user = new User();
      $user['name'] = $username;
      $user['real_name'] = 'Proxy Auth';
      $user->randomizePassword();
      $user->randomizeAPIKey();
      $user['email'] = sprintf('%s@%s', $username, $domain);
      $user['admin'] = false;
      $user->store();

      return $user;
    }

    private function getCookieUser($id) {
        return UserFinder::getById(Cookie::get('id'));
    }

    /**
     * Returns whether this request was a Web request.
     * @return bool Whether this request is thru a web browser.
     */
    public static function isWeb() {
        return in_array(self::$auth_type, [self::T_COOKIE, self::T_PROXY]);
    }

    /**
     * Returns whether this request used standard cookie auth.
     * @return bool Whether this request used standard cookie auth.
     */
    public static function isCookie() {
        return self::$auth_type == self::T_COOKIE;
    }

    /**
     * Returns whether this request used proxy auth.
     * @return bool Whether this request used proxy auth.
     */
    public static function isProxy() {
        return self::$auth_type == self::T_PROXY;
    }

    /**
     * Returns whether this request used api auth.
     * @return bool Whether this request used api auth.
     */
    public static function isAPI() {
        return self::$auth_type == self::T_API;
    }

    /**
     * Login a user.
     * @param string $name The username.
     * @param string $pass The password.
     * @return User|null A User object on success or null.
     */
    public static function login($name, $pass) {
        $user = UserFinder::getByName($name);
        list($user) = Hook::call('auth.login', [$user]);

        if($user && password_verify($pass, $user['password'])) {
            // If necessary, update the password hash.
            if(password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $user['password'] = password_hash($pass, PASSWORD_DEFAULT);
                $user->store();
            }
            self::$auth_type = self::T_COOKIE;
            self::setUser($user);
            return $user;
        }
        return null;
    }

    /**
     * Logout the user.
     */
    public static function logout() {
        self::$auth_type = self::T_NULL;
        self::setUser(null);
    }

    /**
     * Get the currently logged in User.
     * @return User|null The current User or null.
     */
    public static function getUser() {
        return self::$user;
    }

    /**
     * Set the current ID of the user.
     * @return User|null The User or null.
     */
    public static function setUser(User $user=null) {
        if(self::$auth_type == self::T_COOKIE) {
            Cookie::set('id', $user['id']);
        }
        self::$user = $user;
    }

    /**
     * Determines if the current user is logged into the site.
     * @return bool Whether the user is logged in.
     */
    public static function isAuthenticated() {
        return !!self::$user;
    }

    /**
     * Determines if the current user is an admin.
     * @return bool Whether the user is an admin.
     */
    public static function isAdmin() {
        return self::$user && self::$user['admin'];
    }

    /**
     * Get the current ID of the user.
     * @return int A user id.
     */
    public static function getUserId() {
        return is_null(self::$user) ? User::NONE:self::$user['id'];
    }
}
