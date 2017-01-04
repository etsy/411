<?php

class ESClientTest extends TestCase {
    public function unflattenProvider() {
        return [
            [[], []],
            [['a.a' => 'b', 'c' => 'd'], ['a' => ['a' => 'b'], 'c' => 'd']],
            [['a.a.a.a.a.a' => 'b'], ['a' => ['a' =>  ['a' => ['a' => ['a' => ['a' => 'b']]]]]]],
        ];
    }

    /**
     * @dataProvider unflattenProvider
     */
    public function testUnflatten($arr, $expected) {
        $es = new FOO\ESClient(false);

        $this->assertSame($expected, $es->unflatten($arr));
    }
}
