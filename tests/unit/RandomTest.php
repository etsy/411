<?php

class RandomTest extends PHPUnit_Framework_TestCase {
    const NUM_BYTES = 16;

    public function testBytes() {
        $a = FOO\Random::bytes(self::NUM_BYTES);
        $b = FOO\Random::bytes(self::NUM_BYTES);

        $this->assertNotSame($a, $b);
        $this->assertSame(strlen($a), self::NUM_BYTES);
        $this->assertSame(strlen($b), self::NUM_BYTES);
    }

    public function testBase64Bytes() {
        $a = base64_decode(FOO\Random::base64_bytes(self::NUM_BYTES));
        $b = base64_decode(FOO\Random::base64_bytes(self::NUM_BYTES));

        $this->assertNotSame($a, $b);
        $this->assertSame(strlen($a), self::NUM_BYTES);
        $this->assertSame(strlen($b), self::NUM_BYTES);
    }

    public function testHexBytes() {
        $a = hex2bin(FOO\Random::hex_bytes(self::NUM_BYTES));
        $b = hex2bin(FOO\Random::hex_bytes(self::NUM_BYTES));

        $this->assertNotSame($a, $b);
        $this->assertSame(strlen($a), self::NUM_BYTES);
        $this->assertSame(strlen($b), self::NUM_BYTES);
    }
}
