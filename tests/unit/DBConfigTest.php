<?php

class DBConfigTest extends DBTestCase {
    public function testGet() {
        TestHelper::populateDB([
            [FOO\DBConfig::$TABLE, 0, 'a', 'b'],
        ]);

        $cfg = new FOO\DBConfig;
        $this->assertSame('b', $cfg['a']);
        $this->assertSame(null, $cfg['b']);
    }
}
