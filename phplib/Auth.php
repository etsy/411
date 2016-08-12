<?php

namespace FOO;

/**
 * Class Auth
 * Handles authentication related functionality.
 * @package FOO
 */
class Auth {
    /** Invalid user. */
    const NONE = 0;

    private static $user = Auth::NONE;

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
            self::setUserId($user['id']);
            return $user;
        }
        return null;
    }

    /**
     * Logout the user.
     */
    public static function logout() {
        self::setUserId(0);
    }

    /**
     * Get the currently logged in User.
     * @return User|null The current User or null.
     */
    public static function getUser() {
        if(self::$user === Auth::NONE) {
            self::$user = UserFinder::getById(self::getUserId());
        }
        return self::$user;
    }

    /**
     * Determines if the current user is logged into the site.
     * @return bool Whether the user is logged in.
     */
    public static function isAuthenticated() {
        return !!self::getUser();
    }

    /**
     * Determines if the current user is an admin.
     * @return bool Whether the user is an admin.
     */
    public static function isAdmin() {
        $user = self::getUser();
        return $user && $user['admin'];
    }

    /**
     * Set the current ID of the user.
     * @param int $id The user id.
     */
    public static function setUserId($id) {
        Cookie::set('id', $id);
        self::$user = Auth::NONE;
    }
    /**
     * Get the current ID of the user.
     * @return int A user id.
     */
    public static function getUserId() {
        return Cookie::get('id');
    }
}
