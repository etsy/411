<?php

namespace FOO;

/**
 * Class Random
 * Secure random number generator
 * @package FOO
 */
class Random {
    /**
     * Get random bytes.
     * @param int $n The number of bytes.
     * @return string Random bytes.
     */
    public static function bytes($n) {
        $fh = fopen('/dev/urandom', 'r');
        $bytes = fread($fh, $n);
        fclose($fh);
        return $bytes;
    }

    /**
     * Get random bytes base64 encoded.
     * @param int $n The number of bytes.
     * @return string Random bytes.
     */
    public static function base64_bytes($n) {
        return base64_encode(self::bytes($n));
    }

    /**
     * Get random bytes hex encoded.
     * @param int $n The number of bytes.
     * @return string Random bytes.
     */
    public static function hex_bytes($n) {
        return bin2hex(self::bytes($n));
    }
}
