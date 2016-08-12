<?php

namespace FOO;

/**
 * Class Nonce
 * Nonce generation and verification. Can be used for preventing CSRF attacks.
 * @package FOO
 */
class Nonce {
    /**
     * Initialize the class.
     */
    public static function init() {
        if(Cookie::get('nonce') !== null) {
            return;
        }

        self::regenerate();
    }

    /**
     * Regenerate nonce.
     */
    public static function regenerate() {
        Cookie::set('nonce', Random::base64_bytes(24));
    }

    /**
     * Get a new nonce.
     * @return string A nonce.
     */
    public static function get() {
        return Cookie::get('nonce');
    }

    /**
     * Validate a nonce.
     * @param string $nonce The nonce.
     * @return bool Whether the nonce is valid.
     */
    public static function check($nonce) {
        return self::get() === $nonce;
    }
}
