<?php

class HashFilterTest extends DBTestCase {
    public function testProcess() {
        $alert = new FOO\Alert();
        $search = new FOO\Null_Search();
        $hash = $search->getContentHash($alert);
        $alert['content_hash'] = $hash;

        $filter = new FOO\Hash_Filter();
        $this->assertSame([$alert], $filter->process($alert, 0));

        $filter['data']['list'] = [$hash];
        $this->assertSame([], $filter->process($alert, 0));

        $alert_ = new FOO\Alert();
        $alert_['search_id'] = 2;
        $this->assertSame([$alert_], $filter->process($alert_, 0));
    }
}
