<?php

class ThrottleFilterTest extends TestCase {
    public function testProcess() {
        $alert = new FOO\Alert();
        $search = new FOO\Null_Search();

        $filter = new FOO\Throttle_Filter();
        $filter['data']['range'] = 1;
        $filter['data']['count'] = 1;
        $this->assertSame([$alert], $filter->process($alert, 0));

        TestHelper::populateDB([
            [FOO\Alert::$TABLE, 1, 0, time(), '', '', '', '', 0, 0, 0, false, FOO\Alert::ST_NEW, 0, 0, 0, 0]
        ]);
        $this->assertSame([], $filter->process($alert, 0));

        $alert_ = new FOO\Alert();
        $alert_['search_id'] = 2;
        $this->assertSame([$alert_], $filter->process($alert_, 0));
    }
}
