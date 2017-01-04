<?php

class EnricherFilterTest extends TestCase {
    public function testProcess() {
        $filter = new FOO\Enricher_Filter();
        $filter['data']['key'] = 'test';
        $filter['data']['type'] = 'null';
        $alert = new FOO\Alert();
        $alert['content']['test'] = 'test';

        $alert_ = $filter->process($alert, 0)[0];

        $enricher = FOO\Enricher::getEnricher('null');
        $this->assertSame($enricher::process('test'), $alert_['content']['test']);
    }
}
