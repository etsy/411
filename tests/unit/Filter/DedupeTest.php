<?php

class DedupeFilterTest extends DBTestCase {
    public function testProcess() {
        $alert = new FOO\Alert();
        $search = new FOO\Null_Search();
        $hash = $search->getContentHash($alert);
        $alert['content_hash'] = $hash;

        $filter = new FOO\Dedupe_Filter();
        $filter['data']['range'] = 1;
        $this->assertSame([$alert], $filter->process($alert, 0));
        $this->assertSame([], $filter->process($alert, 0));

        $alert_ = new FOO\Alert();
        $alert_['content']['x'] = 'y';
        $hash_ = $search->getContentHash($alert);
        $alert_['content_hash'] = $hash_;
        TestHelper::populateDB([
            [FOO\Alert::$TABLE, 1, 0, time(), '', $hash_, '', 0, 0, 0, false, FOO\Alert::ST_NEW, 0, 0, 0, 0]
        ]);
        $this->assertSame([], $filter->process($alert, 0));

        $alert_['search_id'] = 2;
        $this->assertSame([$alert_], $filter->process($alert_, 0));
    }
}
