<?php

class DBArrayTest extends DBTestCase {
    public function testSet() {
        $cfg = new FOO\DBMeta();
        $cfg['x'] = 'y';

        $this->assertSame('y', $cfg['x']);
    }

    public function testGet() {
        TestHelper::populateDB([
            [FOO\DBMeta::$TABLE, 0, 'x', 'z']
        ]);

        $cfg = new FOO\DBMeta();

        $this->assertSame('z', $cfg['x']);
    }
}
