<?php

class ScriptFilterTest extends DBTestCase {
    public function testProcess() {
        $filter = new FOO\Script_Filter();
        $filter['data']['script'] = 'null';
        $alert = new FOO\Alert();

        $ret = $filter->process($alert, 0);
        $this->assertSame([$alert], $ret);
    }
}
