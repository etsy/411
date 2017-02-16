<?php

namespace FOO;

/**
 * Class Auth
 * Handles authentication related functionality.
 * @package FOO
 */
class Auth {
    private static $user = null;
    private static $api_auth = false;

    public static function init() {
        $user = null;
        if(array_key_exists('HTTP_X_API_KEY', $_SERVER)) {
            self::$api_auth = true;
            if(strlen($_SERVER['HTTP_X_API_KEY']) < User::API_KEY_LEN) {
                return;
            }

            $user = UserFinder::getByAPIKey($_SERVER['HTTP_X_API_KEY']);
        } else if(ProxyAuth::available()) {
            $user = UserFinder::getByName(ProxyAuth::getUserName());
            if (is_null($user) && ProxyAuth::autoSignup()) {
                $user = ProxyAuth::createUser();
            }
        } else {
            $user = UserFinder::getById(Cookie::get('id'));
        }

        list($user) = Hook::call('auth.init', [$user]);
        self::$user = $user;
    }

    /**
     * Returns whether this request was a Web request.
     * @return bool Whether this request is thru a web browser.
     */
    public static function isWeb() {
        return !self::$api_auth;
    }

    /**
     * Returns whether this request was an API request.
     * @return bool Whether this request is thru an API Key.
     */
    public static function isAPI() {
        return self::$api_auth;
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
            self::setUser($user);
            return $user;
        }
        return null;
    }

    /**
     * Logout the user.
     */
    public static function logout() {
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
        if(!self::$api_auth) {
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
