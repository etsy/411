<?php

class NullFilterTest extends TestCase {
    public function testProcess() {
        $filter = new FOO\Null_Filter();
        $alert = new FOO\Alert();

        $ret = $filter->process($alert, 0);
        $this->assertSame([$alert], $ret);
    }
}
