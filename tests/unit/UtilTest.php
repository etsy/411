<?php

class UtilTest extends PHPUnit_Framework_TestCase {
    public function getProvider() {
        return [
            [[], null, null, null],
            [[1], 0, null, 1],
            [[1], 1, null, null],
            [['a' => 1], 'a', null, 1],
            [['a' => 1], 'b', null, null],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($arr, $key, $default, $expected) {
        $this->assertSame(FOO\Util::get($arr, $key, $default), $expected);
    }

    public function existsProvider() {
        return [
            [[], null, false],
            [[1], 0, true],
            [[1], 1, false],
            [['a' => 1], 'a', true],
            [['a' => 1], 'b', false],
        ];
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($arr, $key, $expected) {
        $this->assertSame(FOO\Util::exists($arr, $key), $expected);
    }

    public function escapeProvider() {
        return [
            ['&<>\'"', '&amp;&lt;&gt;&#039;&quot;'],
        ];
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscape($data, $expected) {
        $this->assertSame(FOO\Util::escape($data), $expected);
    }
}
