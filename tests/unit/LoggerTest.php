<?php

class LoggerTest extends TestCase {
    public function serializeProvider() {
        return [
            [null, 'null'],
            ['str', 'str'],
            [true, 1],
            [false, 0],
            [[0, 1], '0:[0] 1:[1]'],
            [[0, []], '0:[0] 1:[]'],
            [[0, 0.0, ['a' => 'b']], '0:[0] 1:[0] 2:[a:[b]]'],
            [[new stdClass()], '0:obj(object)'],
        ];
    }

    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize($arr, $expected) {
        $this->assertSame($expected, FOO\Logger::serialize($arr));
    }
}
