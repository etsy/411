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
        $this->assertSame(ESQuery\Util::get($arr, $key, $default), $expected);
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
        $this->assertSame(ESQuery\Util::exists($arr, $key), $expected);
    }

    public function escapeStringProvider() {
        return [
            ['key:"value" -other:(A B)', 'key\\:\\"value\\"\\ \\-other\\:\\(A\\ B\\)'],
            ['"one" == $two ? 1:2', '\\"one\\"\\ \\=\\=\\ $two\\ \\?\\ 1\\:2'],
        ];
    }

    /**
     * @dataProvider escapeStringProvider
     */
    public function testEscapeString($data, $expected) {
        $this->assertSame(ESQuery\Util::escapeString($data), $expected);
    }

    public function escapeGroupProvider() {
        return [
            [['c', ESQuery\Token::W_STAR, 'ab', ESQuery\Token::W_QMARK], 'c*ab?'],
            [['"', "'", '!'], '\\"\'\\!'],
        ];
    }

    /**
     * @dataProvider escapeGroupProvider
     */
    public function testGroupString($data, $expected) {
        $this->assertSame(ESQuery\Util::escapeGroup($data), $expected);
    }

    public function getIndicesProvider() {
        return [
            [1451606400, 1451606400, ['2016.01.01']],
            [1451606399, 1451606400, ['2015.12.31', '2016.01.01']],
            [1451606399, 1451606400, ['2015.12.31', '2016.01.01']],
            [1451606399, 1452383999, ['2015.12.31', '2016.01.01', '2016.01.02', '2016.01.03', '2016.01.04', '2016.01.05', '2016.01.06', '2016.01.07', '2016.01.08', '2016.01.09']],
        ];
    }

    /**
     * @dataProvider getIndicesProvider
     */
    public function testGetIndices($low, $high, $expected) {
        $this->assertSame(ESQuery\Util::getIndices($low, $high), $expected);
    }

    public function combineProvider() {
        return [
            ['a', [[0, 'b'], [0, 'c']], 1, ['a', 'b', 'c']],
            ['a', [], 0, ['a']],
        ];
    }

    /**
     * @dataProvider combineProvider
     */
    public function testCombine($first, $rest, $idx, $expected) {
        $this->assertSame(ESQuery\Util::combine($first, $rest, $idx), $expected);
    }

    public function assocProvider() {
        return [
            [['a','b'], [[['c','d']], [['e','f']]], 0, ['a'=>'b', 'c'=>'d', 'e'=>'f']],
            [['a','b'], [], 0, ['a'=>'b']],
        ];
    }

    /**
     * @dataProvider assocProvider
     */
    public function testAssoc($first, $rest, $idx, $expected) {
        $this->assertSame(ESQuery\Util::assoc($first, $rest, $idx), $expected);
    }
}
