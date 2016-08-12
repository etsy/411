<?php

class EnricherFilterTest extends DBTestCase {
    public function testProcess() {
        $mac = '00:00:00:00:00';
        $filter = new FOO\Enricher_Filter();
        $filter['data']['key'] = 'mac';
        $filter['data']['type'] = 'mac';
        $alert = new FOO\Alert();
        $alert['content']['mac'] = $mac;

        $alert_ = $filter->process($alert, 0)[0];

        $enricher = FOO\Enricher::getEnricher('mac');
        $this->assertSame($enricher::process($mac), $alert_['content']['mac']);
    }
}
