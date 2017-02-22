<?php

namespace FOO;

/**
 * Class Cookie
 * Manages serializing/deserializing the contents of the cookie and verifying integrity.
 * @package FOO
 */
class Cookie {
    /** Cookie key. */
    const COOKIE_KEY = '411sess';
    /** The lifetime of the cookie. (7 days) */
    const COOKIE_LIFETIME = 604800;
    /** Secret length. */
    const SECRET_LEN = 24;
    /** Cookie separator. */
    const SEP = ':';
    /** @var array Cookie data cache. */
    private static $cache = null;
    /** @var bool Controls whether a cookie will be written to. */
    private static $write = true;
    /** @var bool Denotes whether there is unsaved data in the cookie. */
    private static $dirty = true;

    /**
     * Initialize cookie data.
     */
    public static function init() {
        if(is_null(self::$cache)) {
            self::read();
        }
        header_register_callback(function() { Cookie::write(); });
        new DBConfig;
    }

    /**
     * Get the value of a key in the cookie.
     * @param string $key The key.
     * @return string The value.
     */
    public static function get($key) {
        if(is_null(self::$cache)) {
            self::read();
        }
        return Util::get(self::$cache, $key);
    }

    /**
     * Get all of the values in the cookie
     * @return array All cookie data.
     */
    public static function getAll() {
        return self::$cache;
    }

    /**
     * Set the value of a key in the cookie.
     * @param string $key The key.
     * @param string $text The value.
     */
    public static function set($key, $text) {
        if(is_null(self::$cache)) {
            self::read();
        }
        self::$cache[$key] = $text;
        self::$dirty = true;
    }

    /**
     * Delete a key from the cookie.
     * @param string $key The key.
     */
    public static function delete($key) {
        if(is_null(self::$cache)) {
            self::read();
        }
        unset(self::$cache[$key]);
        self::$dirty = true;
    }

    /**
     * Generate cookie data.
     * @param array $data Cookie data.
     * @param int $date The current timestamp.
     * @return string The encrypted data.
     */
    public static function generate($data, $date) {
        $data['_'] = (int) $date;
        $text = json_encode((object)$data);
        $hash = hash_hmac('sha256', $text, self::getKey());
        return $hash . self::SEP . $text;
    }

    /**
     * Writes out the cookie.
     * @throws \Exception
     */
    public static function write() {
        $site = SiteFinder::getCurrent();
        $domain = explode(':', $site['host'])[0];
        if(self::$write && self::$dirty && Auth::isCookie()) {
            $expiry = $_SERVER['REQUEST_TIME'] + self::COOKIE_LIFETIME;
            setcookie(
                self::COOKIE_KEY,
                self::generate(self::$cache, $_SERVER['REQUEST_TIME']),
                $expiry,
                '/', $domain,
                false, true
            );
        }
        self::$dirty = false;
    }

    /**
     * Reads in the cookie.
     * @throws \Exception
     */
    public static function read() {
        // Empty the cache.
        self::$cache = [];

        // Cookie doesn't exist.
        if(!Util::exists($_COOKIE, self::COOKIE_KEY)) {
            return;
        }
        $ht = $_COOKIE[self::COOKIE_KEY];
        $pos = strpos($ht, self::SEP);

        // Cookie separator not found.
        if($pos === false) {
            return;
        }
        $hash = substr($ht, 0, $pos);
        $text = substr($ht, $pos + 1);

        // Cookie hmac incorrect.
        if(hash_hmac('sha256', $text, self::getKey()) !== $hash) {
            return;
        }
        $data = json_decode($text, true);

        // Invalid json data.
        if(is_null($data)) {
            return;
        }

        // Cookie timestamp expired.
        $expiry = (int) Util::get($data, '_', 0);
        if($_SERVER['REQUEST_TIME'] > $expiry + self::COOKIE_LIFETIME) {
            return;
        }

        self::$cache = is_null($data) ? []:$data;
        self::$dirty = false;
    }

    /**
     * Request that the cookie be updated.
     */
    public static function markDirty() {
        self::$dirty = true;
    }

    /**
     * Returns whether the cookie has been modified.
     * @return bool Whether the cookie has been modified.
     */
    public static function isDirty() {
        return self::$dirty;
    }

    /**
     * Enable/disable writing to the cookie. For testing.
     */
    public static function setWrite($write) {
        self::$write = $write;
    }

    /**
     * Retrieve the cookie secret from the config.
     * @return string The cookie secret.
     * @throws \Exception
     */
    private static function getKey() {
        $cfg = new DBConfig;
        $secret = base64_decode($cfg['cookie_secret']);
        $site_id = SiteFinder::getCurrentId();

        if($site_id != Site::NONE && strlen($secret) < self::SECRET_LEN) {
            throw new CookieException('Cookie secret too short');
        }
        return $secret . Util::get($_SERVER, 'REMOTE_ADDR', 'cli');
    }
}

/**
 * Class CookieException
 * Cookie related errors.
 * @package FOO
 */
class CookieException extends \Exception {}
